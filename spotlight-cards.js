// Spotlight Cards JavaScript - TrafAnalyz Theme
document.addEventListener("DOMContentLoaded", () => {
  // Initialize spotlight effect on feature cards
  const spotlightCards = document.querySelectorAll(".feature-card-spotlight")

  spotlightCards.forEach((card) => {
    // Mouse move handler for spotlight effect
    card.addEventListener("mousemove", (e) => {
      const rect = card.getBoundingClientRect()
      const x = ((e.clientX - rect.left) / rect.width) * 100
      const y = ((e.clientY - rect.top) / rect.height) * 100

      card.style.setProperty("--mouse-x", `${x}%`)
      card.style.setProperty("--mouse-y", `${y}%`)
    })

    // Reset spotlight position when mouse leaves
    card.addEventListener("mouseleave", () => {
      card.style.setProperty("--mouse-x", "50%")
      card.style.setProperty("--mouse-y", "50%")
    })

    // Add smooth entrance animation
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = "1"
            entry.target.style.transform = "translateY(0)"
          }
        })
      },
      { threshold: 0.1 },
    )

    // Set initial state for animation
    card.style.opacity = "0"
    card.style.transform = "translateY(20px)"
    card.style.transition = "opacity 0.6s ease, transform 0.6s ease"

    observer.observe(card)
  })
})
