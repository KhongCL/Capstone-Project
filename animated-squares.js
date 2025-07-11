// Animated Squares Background - TrafAnalyz Theme
class AnimatedSquares {
  constructor(canvas, options = {}) {
    this.canvas = canvas
    this.ctx = canvas.getContext("2d")
    this.options = {
      direction: options.direction || "diagonal",
      speed: options.speed || 0.5,
      borderColor: options.borderColor || "rgba(14, 165, 233, 0.15)",
      squareSize: options.squareSize || 40,
      hoverFillColor: options.hoverFillColor || "rgba(14, 165, 233, 0.05)",
      gradientIntensity: options.gradientIntensity || 0.8,
      ...options,
    }

    this.numSquaresX = 0
    this.numSquaresY = 0
    this.gridOffset = { x: 0, y: 0 }
    this.hoveredSquare = null
    this.animationId = null
    this.isHovered = false

    this.init()
  }

  init() {
    this.resizeCanvas()
    this.setupEventListeners()
    this.startAnimation()
  }

  resizeCanvas() {
    const rect = this.canvas.getBoundingClientRect()
    this.canvas.width = rect.width
    this.canvas.height = rect.height
    this.numSquaresX = Math.ceil(this.canvas.width / this.options.squareSize) + 1
    this.numSquaresY = Math.ceil(this.canvas.height / this.options.squareSize) + 1
  }

  setupEventListeners() {
    window.addEventListener("resize", () => this.resizeCanvas())

    this.canvas.addEventListener("mousemove", (e) => this.handleMouseMove(e))
    this.canvas.addEventListener("mouseenter", () => (this.isHovered = true))
    this.canvas.addEventListener("mouseleave", () => {
      this.hoveredSquare = null
      this.isHovered = false
    })
  }

  handleMouseMove(event) {
    if (!this.isHovered) return

    const rect = this.canvas.getBoundingClientRect()
    const mouseX = event.clientX - rect.left
    const mouseY = event.clientY - rect.top

    const startX = Math.floor(this.gridOffset.x / this.options.squareSize) * this.options.squareSize
    const startY = Math.floor(this.gridOffset.y / this.options.squareSize) * this.options.squareSize

    const hoveredSquareX = Math.floor((mouseX + this.gridOffset.x - startX) / this.options.squareSize)
    const hoveredSquareY = Math.floor((mouseY + this.gridOffset.y - startY) / this.options.squareSize)

    if (!this.hoveredSquare || this.hoveredSquare.x !== hoveredSquareX || this.hoveredSquare.y !== hoveredSquareY) {
      this.hoveredSquare = { x: hoveredSquareX, y: hoveredSquareY }
    }
  }

  drawGrid() {
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height)

    const startX = Math.floor(this.gridOffset.x / this.options.squareSize) * this.options.squareSize
    const startY = Math.floor(this.gridOffset.y / this.options.squareSize) * this.options.squareSize

    // Draw squares
    for (let x = startX; x < this.canvas.width + this.options.squareSize; x += this.options.squareSize) {
      for (let y = startY; y < this.canvas.height + this.options.squareSize; y += this.options.squareSize) {
        const squareX = x - (this.gridOffset.x % this.options.squareSize)
        const squareY = y - (this.gridOffset.y % this.options.squareSize)

        // Fill hovered square
        if (
          this.hoveredSquare &&
          Math.floor((x - startX) / this.options.squareSize) === this.hoveredSquare.x &&
          Math.floor((y - startY) / this.options.squareSize) === this.hoveredSquare.y
        ) {
          this.ctx.fillStyle = this.options.hoverFillColor
          this.ctx.fillRect(squareX, squareY, this.options.squareSize, this.options.squareSize)
        }

        // Draw border
        this.ctx.strokeStyle = this.options.borderColor
        this.ctx.lineWidth = 1
        this.ctx.strokeRect(squareX, squareY, this.options.squareSize, this.options.squareSize)
      }
    }

    // Apply gradient overlay for fade effect
    const gradient = this.ctx.createRadialGradient(
      this.canvas.width / 2,
      this.canvas.height / 2,
      0,
      this.canvas.width / 2,
      this.canvas.height / 2,
      Math.sqrt(this.canvas.width ** 2 + this.canvas.height ** 2) / 2,
    )

    gradient.addColorStop(0, "rgba(0, 0, 0, 0)")
    gradient.addColorStop(1, `rgba(0, 0, 0, ${this.options.gradientIntensity})`)

    this.ctx.fillStyle = gradient
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height)
  }

  updateAnimation() {
    const effectiveSpeed = Math.max(this.options.speed, 0.1)

    switch (this.options.direction) {
      case "right":
        this.gridOffset.x = (this.gridOffset.x - effectiveSpeed + this.options.squareSize) % this.options.squareSize
        break
      case "left":
        this.gridOffset.x = (this.gridOffset.x + effectiveSpeed + this.options.squareSize) % this.options.squareSize
        break
      case "up":
        this.gridOffset.y = (this.gridOffset.y + effectiveSpeed + this.options.squareSize) % this.options.squareSize
        break
      case "down":
        this.gridOffset.y = (this.gridOffset.y - effectiveSpeed + this.options.squareSize) % this.options.squareSize
        break
      case "diagonal":
        this.gridOffset.x = (this.gridOffset.x - effectiveSpeed + this.options.squareSize) % this.options.squareSize
        this.gridOffset.y = (this.gridOffset.y - effectiveSpeed + this.options.squareSize) % this.options.squareSize
        break
    }

    this.drawGrid()
    this.animationId = requestAnimationFrame(() => this.updateAnimation())
  }

  startAnimation() {
    if (this.animationId) {
      cancelAnimationFrame(this.animationId)
    }
    this.updateAnimation()
  }

  destroy() {
    if (this.animationId) {
      cancelAnimationFrame(this.animationId)
    }
    window.removeEventListener("resize", () => this.resizeCanvas())
  }
}

// Initialize animated squares when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  // Initialize squares for overall page background
  const pageCanvas = document.getElementById("page-squares")
  if (pageCanvas) {
    new AnimatedSquares(pageCanvas, {
      direction: "diagonal",
      speed: 0.2,
      borderColor: "rgba(3, 105, 161, 0.2)", // Darker blue for better visibility
      squareSize: 60,
      hoverFillColor: "rgba(14, 165, 233, 0.08)",
      gradientIntensity: 0.05, // Reduced gradient for better visibility
    })
  }

  // Initialize squares for hero section
  const heroCanvas = document.getElementById("hero-squares")
  if (heroCanvas) {
    new AnimatedSquares(heroCanvas, {
      direction: "diagonal",
      speed: 0.3,
      borderColor: "rgba(255, 255, 255, 0.1)",
      squareSize: 50,
      hoverFillColor: "rgba(255, 255, 255, 0.05)",
      gradientIntensity: 0.6,
    })
  }

  // Initialize squares for features section
  const featuresCanvas = document.getElementById("features-squares")
  if (featuresCanvas) {
    new AnimatedSquares(featuresCanvas, {
      direction: "right",
      speed: 0.2,
      borderColor: "rgba(14, 165, 233, 0.08)",
      squareSize: 35,
      hoverFillColor: "rgba(14, 165, 233, 0.03)",
      gradientIntensity: 0.9,
    })
  }

  // Initialize squares for auth pages
  const authCanvas = document.getElementById("auth-squares")
  if (authCanvas) {
    new AnimatedSquares(authCanvas, {
      direction: "right",
      speed: 0.15,
      borderColor: "rgba(3, 105, 161, 0.25)", // Darker blue for auth pages
      squareSize: 45,
      hoverFillColor: "rgba(14, 165, 233, 0.06)",
      gradientIntensity: 0.1, // Minimal gradient for auth pages
    })
  }
})
