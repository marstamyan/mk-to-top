;(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const mkBtn = document.querySelector(".mk-to-top-btn")
    if (!mkBtn) return

    const parseAdaptiveSize = (value) => {
      const base = parseFloat(value)
      if (isNaN(base)) return null
      const min = Math.round(base * 0.75)
      const max = Math.round(base * 1.25)
      const vw = (base / 4).toFixed(2)
      return `clamp(${min}px, ${vw}vw, ${max}px)`
    }

    const isCustomUnit = (val) =>
      typeof val === "string" && /px|vw|vh|em|rem|%|clamp/.test(val)

    const mkBtnRawWidth = mkBtn.dataset.mkBtnWidth || "40"
    const mkBtnRawHeight = mkBtn.dataset.mkBtnHeight || "40"

    const mkBtnWidth = isCustomUnit(mkBtnRawWidth)
      ? mkBtnRawWidth
      : parseAdaptiveSize(mkBtnRawWidth) || "clamp(40px, 10vw, 60px)"

    const mkBtnHeight = isCustomUnit(mkBtnRawHeight)
      ? mkBtnRawHeight
      : parseAdaptiveSize(mkBtnRawHeight) || "clamp(40px, 10vw, 60px)"

    const mkBtnIconColor = mkBtn.dataset.mkBtnIconColor?.trim() || "#fff"
    const mkBtnBgColor = mkBtn.dataset.mkBtnBgColor?.trim() || "#000"

    const mkBtnPosition = ["left", "right"].includes(
      mkBtn.dataset.mkBtnPosition,
    )
      ? mkBtn.dataset.mkBtnPosition
      : "right"

    const mkBtnRawOffset = mkBtn.dataset.mkBtnOffset || "30"
    const mkBtnOffset = isCustomUnit(mkBtnRawOffset)
      ? mkBtnRawOffset
      : parseAdaptiveSize(mkBtnRawOffset) || "clamp(22px, 4vw, 38px)"

    const mkBtnShowAt = !isNaN(parseInt(mkBtn.dataset.mkBtnShowAt, 10))
      ? parseInt(mkBtn.dataset.mkBtnShowAt, 10)
      : 300
    const mkBtnMobile = mkBtn.dataset.mkBtnMobile === "true"

    mkBtn.style.setProperty("--mk-btn-width", mkBtnWidth)
    mkBtn.style.setProperty("--mk-btn-height", mkBtnHeight)
    mkBtn.style.setProperty("--mk-btn-bg-color", mkBtnBgColor)
    mkBtn.style.setProperty("--mk-btn-icon-color", mkBtnIconColor)

    mkBtn.style.bottom = mkBtnOffset
    mkBtn.style[mkBtnPosition] = mkBtnOffset

    function mkIsMobile() {
      return window.innerWidth <= 768
    }

    function mkToggleBtn() {
      const scrolled = window.scrollY || document.documentElement.scrollTop

      if (!mkBtnMobile && mkIsMobile()) {
        mkBtn.classList.remove("mk-visible")
        return
      }

      if (scrolled > mkBtnShowAt) {
        mkBtn.classList.add("mk-visible")
      } else {
        mkBtn.classList.remove("mk-visible")
      }
    }

    mkBtn.addEventListener("click", function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      })
    })

    window.addEventListener("scroll", mkToggleBtn)
    window.addEventListener("resize", mkToggleBtn)
    mkToggleBtn()
  })
})()
