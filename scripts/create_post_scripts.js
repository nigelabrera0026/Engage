/*******w******** 
    
    @author: Nigel Abrera
    @date: 11/8/2023
    @description: Logic for previewing the page.

****************/

document.addEventListener("DOMContentLoaded", load);


function load() {

    /**
     * Function to display image preview
     * @param input File path 
     * */ 
    function readURL(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById('image_preview').src = e.target.result;
                document.getElementById('image_preview').style.display = 'block';
            };

            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Trigger the function when a file is selected
    let imageCoverInput = document.getElementById("image_cover");
    if (imageCoverInput) {
        imageCoverInput.addEventListener("change", function () {
            readURL(this);
        });
        
    }

    // Function to remove the image
    let removeImageButton = document.getElementById("remove_image");
    if(removeImageButton) {
        removeImageButton.addEventListener("click", () => {
            document.getElementById("image_preview").src = '#';
            document.getElementById('image_preview').style.display = 'none';
            document.getElementById('image_cover').value = '';
        });
    }
}