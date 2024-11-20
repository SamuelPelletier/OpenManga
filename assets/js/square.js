const appId = 'sandbox-sq0idb-1_0lhWVrpugvQ2XCgE3gUw';  // Application ID
const locationId = 'LJECM8RPP7J5P'; // Location ID
const payments = Square.payments(appId, locationId);

async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container');
    return card;
}

async function tokenize(paymentMethod) {
    const result = await paymentMethod.tokenize();
    if (result.status === 'OK') {
        return result.token;
    } else {
        console.error(result.errors);
        return null;
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    const card = await initializeCard(payments);

    document.getElementById('card-button').addEventListener('click', async function () {
        const token = await tokenize(card);
        if (token) {
            fetch('pay_proceed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source_id: token
                }),
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('payment-status-container').innerText = data.message;
                    document.getElementById('payment-details-container').innerText = data.details;
                })
                .catch(error => {
                    document.getElementById('payment-status-container').innerText = "Failed";
                    document.getElementById('payment-details-container').innerText = "Unexpected error";
                });
        }
    });
});
