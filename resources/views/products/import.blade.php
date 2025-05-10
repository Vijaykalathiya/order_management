@extends('layouts.app')

@section('title', 'Import Products')

@section('content')
    <h1 class="page-title">Upload Product Excel File</h1>

    <form id="uploadForm" enctype="multipart/form-data" class="upload-form">
        <input type="file" name="file" id="fileInput" required accept=".xlsx,.xls" class="input-file">
        <span id="fileNameDisplay" class="file-name-display"></span>

        <button type="submit" class="btn-primary" id="uploadBtn">Upload</button>

        <div id="loader" class="loader" style="display: none;"></div>
    </form>

    <div id="result" class="upload-result"></div>
@endsection

@section('scripts')
    <script>
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const loader = document.getElementById('loader');
        const resultDiv = document.getElementById('result');
        const uploadBtn = document.getElementById('uploadBtn');

        fileInput.addEventListener('change', function () {
            const fileName = this.files[0]?.name || '';
            fileNameDisplay.textContent = fileName ? `Selected: ${fileName}` : '';
        });

        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            loader.style.display = 'inline-block';
            uploadBtn.disabled = true;
            resultDiv.textContent = '';

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch("{{ route('products.import') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                    },
                    body: formData
                });

                const text = await response.text();

                if (response.ok) {
                    resultDiv.innerHTML = `<p class="success-message">Upload successful: ${text}</p>`;
                } else {
                    resultDiv.innerHTML = `<p class="error-message">Upload failed: ${text}</p>`;
                }
            } catch (err) {
                resultDiv.innerHTML = `<p class="error-message">An error occurred: ${err.message}</p>`;
            } finally {
                loader.style.display = 'none';
                uploadBtn.disabled = false;
            }
        });
    </script>
@endsection
