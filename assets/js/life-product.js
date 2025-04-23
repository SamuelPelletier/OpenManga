document.addEventListener('DOMContentLoaded', async function () {
    document.getElementById('main').innerHTML = document.getElementById('main').innerHTML.replace(/( lifetime)/gi, '<span class="lifetime">$1</span>');
    let clickEvent = async function (event) {
        let ref = event.target
        let parentId = ref.parentElement.id
        ref.style.display = "none";
        document.querySelector("#" + parentId + ' .loader-payment').style.display = "block";
        fetch('life_product_proceed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
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
    };
    document.getElementById('credit-button')?.addEventListener('click', clickEvent);
});
