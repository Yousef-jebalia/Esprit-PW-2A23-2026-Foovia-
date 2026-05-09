document.addEventListener('DOMContentLoaded', () => {
  const revealItems = Array.from(document.querySelectorAll('[data-awareness-reveal]'));
  const counters = Array.from(document.querySelectorAll('[data-count-to]'));

  const formatNumber = (value) => {
    if (value >= 1000000000) return `${(value / 1000000000).toFixed(1)}B`;
    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    return String(Math.round(value));
  };

  const animateCounter = (node) => {
    if (node.dataset.counted === 'true') return;
    node.dataset.counted = 'true';
    const target = Number(node.dataset.countTo || 0);
    const duration = 1100;
    const startedAt = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      node.textContent = formatNumber(target * eased);
      if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      entry.target.querySelectorAll('[data-count-to]').forEach(animateCounter);
      if (entry.target.matches('[data-count-to]')) animateCounter(entry.target);
    });
  }, { threshold: 0.22 });

  revealItems.forEach((item) => observer.observe(item));
  counters.forEach((counter) => observer.observe(counter));

  const slider = document.querySelector('[data-waste-slider]');
  const weekly = document.querySelector('[data-waste-weekly]');
  const yearly = document.querySelector('[data-waste-yearly]');
  const meals = document.querySelector('[data-waste-meals]');
  const score = document.querySelector('[data-waste-score]');

  const updateCalculator = () => {
    if (!slider || !weekly || !yearly || !meals || !score) return;
    const kg = Number(slider.value || 0);
    const yearlyKg = kg * 52;
    weekly.textContent = String(kg);
    yearly.textContent = String(yearlyKg);
    meals.textContent = String(yearlyKg * 2);
    score.textContent = kg >= 14 ? 'Planet champion' : kg >= 8 ? 'Strong shift' : 'Strong start';
  };

  slider?.addEventListener('input', updateCalculator);
  updateCalculator();
});
