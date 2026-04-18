document.addEventListener('DOMContentLoaded', () => {
  const deleteForms = document.querySelectorAll('[data-delete-product-form]');

  deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const productName = form.dataset.productName || 'this product';
      const confirmed = window.confirm(`Delete ${productName} from the marketplace?`);

      if (!confirmed) {
        event.preventDefault();
      }
    });
  });

  const deleteStoreForms = document.querySelectorAll('[data-delete-store-form]');

  deleteStoreForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const storeName = form.dataset.storeName || 'this magasin';
      const confirmed = window.confirm(`Delete ${storeName} from Foovia magasins? Product links to this magasin will also be removed.`);

      if (!confirmed) {
        event.preventDefault();
      }
    });
  });
});
