(function () {
    var takePicture = document.querySelector("#take-picture"),
        showPicture = document.querySelector("#show-picture"),
        tagField = document.querySelector("#tag"),
        teamField = document.querySelector("#team"),
        sendStatusContainer = document.querySelector("#send-status"),
        uploadSizeContainer = document.querySelector("#upload-size"),
        previewContainer = document.querySelector("#preview"),
        sendButton = document.querySelector("#send-button");

    function log(msg) {
        var error = document.querySelector("#error");
        if (error) {
            error.innerHTML = msg;
        }
    }

    function readFile(file) {
        var reader = new FileReader();

        reader.onloadend = function () {
            processFile(reader.result, file.type);
        };

        reader.onerror = function () {
            alert('There was an error reading the file!');
        };

        reader.readAsDataURL(file);
    }

    function processFile(dataURL, fileType) {
        var maxSize = 1000;
        var image = new Image();
        image.src = dataURL;

        image.onload = function () {
            var width = image.width;
            var height = image.height;
            var shouldResize = (width > maxSize) || (height > maxSize);

            if (shouldResize) {
                var newWidth;
                var newHeight;

                if (width > height) {
                    newHeight = height * (maxSize / width);
                    newWidth = maxSize;
                } else {
                    newWidth = width * (maxSize / height);
                    newHeight = maxSize;
                }

                var canvas = document.createElement('canvas');

                canvas.width = newWidth;
                canvas.height = newHeight;

                var context = canvas.getContext('2d');

                context.drawImage(this, 0, 0, newWidth, newHeight);

                dataURL = canvas.toDataURL(fileType);
            }

            showPicture.src = dataURL;
            previewContainer.classList.remove('hidden');

            //sendFile(dataURL);

            uploadSizeContainer.innerHTML = Math.round(dataURL.length / 100000) / 10000;

            checkForm();
        };

        image.onerror = function () {
            previewContainer.classList.add('hidden');
            alert('There was an error processing your file!');

            previewContainer.classList.add('hidden');

            checkForm();
        };
    }

    function sendFile(fileData, tag, team) {
        var formData = new FormData();

        formData.append('imageData', fileData);
        formData.append('tag', tag);
        formData.append('team', team);

        $.ajax({
            type: 'POST',
            url: '/medlemsregister/upload.php',
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
                if (data.success) {
                    alert('You successfully uploaded ' + data.recv + ' bytes!');
                } else {
                    alert('1: There was an error uploading your file!' + data);
                }
            },
            error: function (data) {
                alert('2: There was an error uploading your file!' + data);
            }
        });
    }

    function checkForm() {
        var isImageSelected = !previewContainer.classList.contains('hidden');
        var isTagSelected = tagField.value != '';
        var isTeamSelected = teamField.value != '';
        if (isImageSelected && isTagSelected && isTeamSelected) {
            sendStatusContainer.classList.add("hidden");
            return true;
        } else {
            sendStatusContainer.classList.remove("hidden");
            if (!isImageSelected && !isTagSelected && !isTeamSelected) {
                sendStatusContainer.innerHTML = "V&auml;lj lag, bild och motiv. D&auml;refter kan du trycka p&aring; Skicka.";
            } else {
                sendStatusContainer.innerHTML = "Du m&aring;ste fylla i hela formul&auml;ret innan du kan trycka p&aring; Skicka.";
            }
            return false;
        }
    }

    sendButton.onclick = function () {
        if (checkForm()) {
            sendFile(showPicture.src, tagField.value, teamField.value);
        }
    };

    if (takePicture && showPicture) {
        // Set events
        takePicture.onchange = function (event) {
            // Get a reference to the taken picture or chosen file
            var files = event.target.files,
                file;

            if (files && files.length > 0) {
                file = files[0];
                if (file) {
                    if (/^image\//i.test(file.type)) {
                        readFile(file);
                    } else {
                        log('Not a valid image!');
                    }
                }

                /*
                 try {
                 // Create ObjectURL
                 var imgURL = window.URL.createObjectURL(file);

                 // Set img src to ObjectURL
                 showPicture.src = imgURL;

                 log(imgURL);

                 // Revoke ObjectURL
                 URL.revokeObjectURL(imgURL);
                 }
                 catch (e) {
                 try {
                 // Fallback if createObjectURL is not supported
                 var fileReader = new FileReader();
                 fileReader.onload = function (event) {
                 showPicture.src = event.target.result;
                 };
                 fileReader.readAsDataURL(file);
                 }
                 catch (e) {
                 //
                 log("Neither createObjectURL or FileReader are supported");
                 }
                 }
                 */
            }
        };
    }
})();
