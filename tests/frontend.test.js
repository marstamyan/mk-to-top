const test = require("node:test")
const assert = require("node:assert/strict")
const fs = require("node:fs")
const path = require("node:path")
const vm = require("node:vm")

const script = fs.readFileSync(path.join(__dirname, "../public/js/mk-to-top-button.js"), "utf8")

function setup(options = {}) {
  const windowListeners = {}
  const documentListeners = {}
  const buttonListeners = {}
  const attributes = {}
  const styles = {}
  const classes = new Set()
  const frames = []
  const scrolls = []
  const button = {
    dataset: { mkBtnWidth: "40", mkBtnHeight: "40", mkBtnOffset: "30", mkBtnShowAt: "300", mkBtnMobile: "true", ...options.dataset },
    hidden: true,
    tabIndex: -1,
    style: { setProperty(name, value) { styles[name] = value } },
    classList: { toggle(name, on) { on ? classes.add(name) : classes.delete(name) } },
    setAttribute(name, value) { attributes[name] = value },
    addEventListener(name, callback) { (buttonListeners[name] ||= []).push(callback) },
  }
  const document = {
    readyState: options.readyState || "complete",
    documentElement: { scrollTop: 0 },
    getElementById() { return options.noButton ? null : button },
    addEventListener(name, callback, config) { documentListeners[name] = { callback, config } },
  }
  const window = {
    innerWidth: options.innerWidth || 1200,
    scrollY: options.scrollY || 0,
    CSS: { supports: (property, value) => !value.includes("invalid") },
    matchMedia: () => ({ matches: !!options.reduceMotion }),
    scrollTo(value) { scrolls.push(value) },
    requestAnimationFrame(callback) { frames.push(callback) },
    addEventListener(name, callback, config) { (windowListeners[name] ||= []).push({ callback, config }) },
  }
  const context = vm.createContext({ document, window })
  function execute() { vm.runInContext(script, context) }
  function event(name) { for (const { callback } of windowListeners[name] || []) callback() }
  function flush() { while (frames.length) frames.shift()() }
  function click() { for (const callback of buttonListeners.click || []) callback() }
  execute()
  return { button, document, window, frames, classes, attributes, styles, scrolls, windowListeners, documentListeners, buttonListeners, execute, event, flush, click }
}

test("waits for DOMContentLoaded, then initializes a hidden, unfocusable button", () => {
  const page = setup({ readyState: "loading" })
  assert.equal(page.button.dataset.mkToTopReady, undefined)
  assert.equal(page.documentListeners.DOMContentLoaded.config.once, true)
  page.documentListeners.DOMContentLoaded.callback()
  assert.equal(page.button.hidden, true)
  assert.equal(page.button.tabIndex, -1)
  assert.equal(page.attributes["aria-hidden"], "true")
  assert.equal(page.styles["--mk-btn-width"], "clamp(30px, 10.00vw, 50px)")
})

test("initializes immediately when loaded after DOMContentLoaded at a restored scroll position", () => {
  const page = setup({ scrollY: 900 })
  assert.equal(page.button.hidden, false)
  assert.equal(page.button.tabIndex, 0)
  assert.equal(page.attributes["aria-hidden"], "false")
  assert.equal(page.classes.has("mk-visible"), true)
})

test("crossing the scroll threshold updates focusability and accessibility together", () => {
  const page = setup()
  page.window.scrollY = 300
  page.event("scroll")
  page.flush()
  assert.equal(page.button.hidden, true)
  page.window.scrollY = 301
  page.event("scroll")
  page.flush()
  assert.equal(page.button.hidden, false)
  assert.equal(page.button.tabIndex, 0)
  page.window.scrollY = 0
  page.event("scroll")
  page.flush()
  assert.equal(page.button.hidden, true)
  assert.equal(page.button.tabIndex, -1)
  assert.equal(page.attributes["aria-hidden"], "true")
  assert.equal(page.classes.has("mk-visible"), false)
})

test("mobile restriction responds to viewport resizing at the 768px boundary", () => {
  const page = setup({ scrollY: 900, innerWidth: 768, dataset: { mkBtnMobile: "false" } })
  assert.equal(page.button.hidden, true)
  page.window.innerWidth = 769
  page.event("resize")
  page.flush()
  assert.equal(page.button.hidden, false)
  page.window.innerWidth = 500
  page.event("resize")
  page.flush()
  assert.equal(page.button.hidden, true)
  assert.equal(page.button.tabIndex, -1)
})

test("mobile display is enabled by default", () => {
  const page = setup({ scrollY: 900, innerWidth: 400, dataset: { mkBtnMobile: undefined } })
  assert.equal(page.button.hidden, false)
})

test("scroll and resize work is coalesced into one animation frame", () => {
  const page = setup()
  for (let i = 0; i < 20; i++) page.event("scroll")
  page.event("resize")
  assert.equal(page.frames.length, 1)
  assert.equal(page.windowListeners.scroll[0].config.passive, true)
  page.window.scrollY = 1000
  page.flush()
  assert.equal(page.button.hidden, false)
  page.event("scroll")
  assert.equal(page.frames.length, 1)
})

test("a pageshow event refreshes visibility after browser history restoration", () => {
  const page = setup()
  page.window.scrollY = 500
  page.event("pageshow")
  page.flush()
  assert.equal(page.button.hidden, false)
})

test("click scrolls to the top smoothly", () => {
  const page = setup({ scrollY: 900 })
  page.click()
  assert.equal(page.scrolls.length, 1)
  assert.equal(page.scrolls[0].top, 0)
  assert.equal(page.scrolls[0].behavior, "smooth")
})

test("reduced motion requests instantaneous scrolling even on themes with CSS smooth scrolling", () => {
  const page = setup({ scrollY: 900, reduceMotion: true })
  page.click()
  assert.equal(page.scrolls[0].behavior, "instant")
})

test("rechecks the reduced motion preference for each click", () => {
  const page = setup({ scrollY: 900 })
  page.click()
  page.window.matchMedia = () => ({ matches: true })
  page.click()
  assert.equal(page.scrolls[0].behavior, "smooth")
  assert.equal(page.scrolls[1].behavior, "instant")
})

test("zero offset and threshold are preserved", () => {
  const page = setup({ scrollY: 1, dataset: { mkBtnOffset: "0", mkBtnShowAt: "0", mkBtnPosition: "left" } })
  assert.equal(page.button.style.bottom, "clamp(0px, 0.00vw, 0px)")
  assert.equal(page.button.style.left, "clamp(0px, 0.00vw, 0px)")
  assert.equal(page.button.style.right, undefined)
  assert.equal(page.button.hidden, false)
})

test("invalid dimensions and thresholds fall back to working defaults", () => {
  const page = setup({ scrollY: 100, dataset: { mkBtnWidth: "-4", mkBtnHeight: "0", mkBtnOffset: "invalid", mkBtnShowAt: "10junk", mkBtnPosition: "top" } })
  assert.equal(page.styles["--mk-btn-width"], "clamp(30px, 10.00vw, 50px)")
  assert.equal(page.styles["--mk-btn-height"], "clamp(30px, 10.00vw, 50px)")
  assert.equal(page.button.style.bottom, "clamp(23px, 7.50vw, 38px)")
  assert.equal(page.button.style.right, page.button.style.bottom)
  assert.equal(page.button.hidden, true)
})

test("explicit CSS lengths and clamp values remain supported", () => {
  const page = setup({ dataset: { mkBtnWidth: "3rem", mkBtnHeight: "clamp(30px, 5vw, 60px)", mkBtnOffset: "12px" } })
  assert.equal(page.styles["--mk-btn-width"], "3rem")
  assert.equal(page.styles["--mk-btn-height"], "clamp(30px, 5vw, 60px)")
  assert.equal(page.button.style.bottom, "12px")
})

test("loading the script twice does not register duplicate handlers", () => {
  const page = setup({ scrollY: 900 })
  page.execute()
  assert.equal(page.windowListeners.scroll.length, 1)
  assert.equal(page.buttonListeners.click.length, 1)
  page.click()
  assert.equal(page.scrolls.length, 1)
})

test("a page without a button remains unaffected", () => {
  const page = setup({ noButton: true })
  assert.deepEqual(page.windowListeners, {})
  assert.equal(page.frames.length, 0)
})
