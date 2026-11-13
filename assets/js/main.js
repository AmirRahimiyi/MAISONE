
const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.navigation');

if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
        navigation.classList.toggle('active');
    });
}

document.addEventListener('click', async function (event) {

    const button = event.target.closest('.summary-qty-btn');

    if (!button) return;
    console.log('QUANTITY BUTTON CLICKED');

    const item = button.closest('.summary-item');
    const quantityElement = item.querySelector('.summary-qty');

    const productId = item.dataset.productId;
    const currentQuantity = parseInt(quantityElement.textContent);

    let newQuantity = currentQuantity;

    if (button.dataset.action === 'plus') {
        newQuantity++;
    } else if (button.dataset.action === 'minus') {
        newQuantity--;
    }

    if (newQuantity < 1) return;

    button.disabled = true;

    try {

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);

       const response = await fetch(
    '/MAISONE/pages/update-checkout-quantity.php',
    {
        method: 'POST',
        body: formData
    }
);

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Update failed');
        }

        quantityElement.textContent = data.quantity;

        item.querySelector('.summary-subtotal').textContent =
            '€' + data.subtotal.toFixed(2);

        document.querySelector('.checkout-grand-total').textContent =
            '€' + data.total.toFixed(2);

    } catch (error) {

        alert(error.message);

    } finally {

        button.disabled = false;

    }

});
