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
        return null;
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    document.getElementById('main').innerHTML = document.getElementById('main').innerHTML.replace(/(\b premium\b)/gi, '<span class="premium">$1</span>');
    const card = await initializeCard(payments);
    let clickEvent = async function (event) {
        let ref = event.target
        let parentId = ref.parentElement.id
        ref.style.display = "none";
        document.querySelector("#" + parentId + ' .loader-payment').style.display = "block";
        let amount = document.querySelector("#" + parentId + ' .price-select')?.value
        let url = 'pay_proceed';
        if (amount) {
            url = 'credit_proceed';
        }

        if (ref.id === "credit-button") {
            url = 'subscribe_proceed';
        }
        const token = await tokenize(card);
        if (token || ref.id === "credit-button") {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source_id: token,
                    amount: amount
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success == false) {
                        ref.style.display = "block";
                        document.querySelector("#" + parentId + ' .loader-payment').style.display = "none";
                        document.querySelector("#" + parentId + ' .payment-status-container').innerText = data.message;
                        document.querySelector("#" + parentId + ' .payment-details-container').innerText = data.details;
                    } else {
                        document.querySelector("#" + parentId + ' .loader-payment').style.display = "none";
                        document.querySelector("#" + parentId + ' .success').style.display = "block";
                        document.querySelector("#" + parentId + ' .payment-status-container').innerText = "";
                        document.querySelector("#" + parentId + ' .payment-details-container').innerText = "";
                    }
                })
                .catch(error => {
                    document.querySelector("#" + parentId + ' .payment-status-container').innerText = "Failed";
                    document.querySelector("#" + parentId + ' .payment-details-container').innerText = "Unexpected error";
                    ref.style.display = "block";
                    document.querySelector("#" + parentId + ' .loader').style.display = "none";
                });
        }
    }
    document.getElementById('card-button').addEventListener('click', clickEvent);
    document.getElementById('credit-button')?.addEventListener('click', clickEvent);

    /*let previousValue = document.querySelector('.price-select')?.value;
    if(previousValue) {
        document.getElementById('card-payment-form-container').innerHTML = document.getElementById('card-payment-form-container').innerHTML.replace(/(#credits#)/gi, previousValue);
        document.querySelector('.price-select').addEventListener('change', function (event) {
            document.querySelector('#card-payment-form-container .price-text').innerHTML = document.querySelector('#card-payment-form-container .price-text').innerHTML.replace(" " + previousValue.toString() + " credits", " " + event.target.value + " credits");
            document.querySelector('#card-payment-form-container .star-button').innerHTML = document.querySelector('#card-payment-form-container .star-button').innerHTML.replace(" " + previousValue.toString() + " credits", " " + event.target.value + " credits");
            previousValue = event.target.value
        });
    }*/
});
