(() => {
  const toggle = document.querySelector('.nav-toggle');
  const menu = document.getElementById('mainMenu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      menu.classList.toggle('is-open', !expanded);
    });
  }

  const cpfInputs = document.querySelectorAll('[data-cpf-input]');
  cpfInputs.forEach((input) => {
    input.addEventListener('input', () => {
      let v = input.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 9) {
        v = `${v.slice(0, 3)}.${v.slice(3, 6)}.${v.slice(6, 9)}-${v.slice(9)}`;
      } else if (v.length > 6) {
        v = `${v.slice(0, 3)}.${v.slice(3, 6)}.${v.slice(6)}`;
      } else if (v.length > 3) {
        v = `${v.slice(0, 3)}.${v.slice(3)}`;
      }
      input.value = v;
    });
  });
})();

