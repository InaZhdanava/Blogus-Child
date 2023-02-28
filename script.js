document.addEventListener('DOMContentLoaded', function () {

    let filter = document.querySelector('#filter');
	let button = document.querySelector('#filerButton');
    let responseElement = document.querySelector('#response');

	if (filter && button) {

        getData();

        button.addEventListener('click', function (e) {
            e.preventDefault();
            getData();
        });
    }

    function getData() {
		
        button.innerText = 'Processing...';
        const data = new FormData();

        data.append('action', 'myfilter');
        data.append('genresfilter', filter.querySelector('[name="genresfilter"]').value);


        fetch('/wp-admin/admin-ajax.php', {
            method: "POST",
            body: data
        })
		.then((response) => response.json())
		.then((data) => {
			if (data) {
				button.innerText = 'Apply filter';
				responseElement.innerHTML = data['posts'];
			}
		})
		.catch((error) => {
			console.error(error);
		});
    }
});