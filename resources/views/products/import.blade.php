<!DOCTYPE html>
<html>
<head>
    <title>Import Products</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        #result { margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>Upload Product Excel File</h1>

    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="file" id="fileInput" required accept=".xlsx,.xls">
        <button type="submit">Upload</button>
    </form>

    <div id="result"></div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData();
            const fileInput = document.getElementById('fileInput');
            formData.append('file', fileInput.files[0]);

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch("{{ route('products.import') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                },
                body: formData
            });

            const resultDiv = document.getElementById('result');

            if (response.ok) {
                const text = await response.text();
                resultDiv.innerHTML = `<p style="color: green;">Upload successful: ${text}</p>`;
            } else {
                const error = await response.text();
                resultDiv.innerHTML = `<p style="color: red;">Upload failed: ${error}</p>`;
            }
        });
    </script>
</body>
</html>
