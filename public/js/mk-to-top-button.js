;(function () {
  function init() {
    const mkBtn = document.getElementById("mk-to-top-btn")
    if (!mkBtn || mkBtn.dataset.mkToTopReady === "true") return
    mkBtn.dataset.mkToTopReady = "true"

    function adaptiveSize(value, fallback, allowZero) {
      const raw = typeof value === "string" ? value.trim() : ""
      if (/^(?:\d+(?:\.\d+)?|\.\d+)$/.test(raw)) {
        const base = Number(raw)
        if (Number.isFinite(base * 1.25) && (base > 0 || (allowZero && base === 0))) {
          return `clamp(${Math.round(base * 0.75)}px, ${(base / 4).toFixed(2)}vw, ${Math.round(base * 1.25)}px)`
        }
      }

      // Keep support for explicit CSS lengths used by older installations.
      const isLength = /^(?:\d+(?:\.\d+)?|\.\d+)(?:px|vw|vh|em|rem|%)$/i.test(raw)
      const isClamp = /^clamp\([\d\s.,+*/%a-z()-]+\)$/i.test(raw)
      if ((isLength || isClamp) && window.CSS && window.CSS.supports("width", raw)) {
        if (allowZero || !/^0(?:\.0+)?(?:px|vw|vh|em|rem|%)$/i.test(raw)) return raw
      }

      return adaptiveSize(fallback, "40", allowZero)
    }

    const width = adaptiveSize(mkBtn.dataset.mkBtnWidth, "40", false)
    const height = adaptiveSize(mkBtn.dataset.mkBtnHeight, "40", false)
    const offset = adaptiveSize(mkBtn.dataset.mkBtnOffset, "30", true)
    const position = mkBtn.dataset.mkBtnPosition === "left" ? "left" : "right"
    const rawShowAt = mkBtn.dataset.mkBtnShowAt
    const showAt = typeof rawShowAt === "string" && /^\d+$/.test(rawShowAt) && Number.isFinite(Number(rawShowAt))
      ? Number(rawShowAt)
      : 300
    const showOnMobile = mkBtn.dataset.mkBtnMobile !== "false"

    mkBtn.style.setProperty("--mk-btn-width", width)
    mkBtn.style.setProperty("--mk-btn-height", height)
    mkBtn.style.setProperty("--mk-btn-bg-color", mkBtn.dataset.mkBtnBgColor?.trim() || "#000")
    mkBtn.style.setProperty("--mk-btn-icon-color", mkBtn.dataset.mkBtnIconColor?.trim() || "#fff")
    mkBtn.style.bottom = offset
    mkBtn.style[position] = offset

    let wasVisible
    function updateVisibility() {
      const scrolled = window.scrollY || document.documentElement.scrollTop || 0
      const visible = scrolled > showAt && (showOnMobile || window.innerWidth > 768)
      if (visible === wasVisible) return
      wasVisible = visible
      mkBtn.hidden = !visible
      mkBtn.tabIndex = visible ? 0 : -1
      mkBtn.setAttribute("aria-hidden", visible ? "false" : "true")
      mkBtn.classList.toggle("mk-visible", visible)
    }

    let updatePending = false
    function scheduleUpdate() {
      if (updatePending) return
      updatePending = true
      window.requestAnimationFrame(function () {
        updatePending = false
        updateVisibility()
      })
    }

    mkBtn.addEventListener("click", function () {
      const reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches
      window.scrollTo({ top: 0, behavior: reduceMotion ? "instant" : "smooth" })
    })

    window.addEventListener("scroll", scheduleUpdate, { passive: true })
    window.addEventListener("resize", scheduleUpdate, { passive: true })
    window.addEventListener("pageshow", scheduleUpdate)
    updateVisibility()
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true })
  } else {
    init()
  }
})()
