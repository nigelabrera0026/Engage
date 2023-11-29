/*******w******** 
    
    @author: Nigel Abrera
    @date: 11/8/2023
    @description: Logic for previewing the page.

****************/
/*
    TODO FIXME REMOVE BUTTON FOR EITHER IMG AND AUDIO IS NOT CHANGING. NO LEADS.
*/
document.addEventListener("DOMContentLoaded", function() {

    // document.getElementById("remove_image").className.remove("hidden");

    /**
     * Function to display image preview.
     * @param input File path 
     * */ 
    function readURL(input, previewElement, removeButton) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();

            reader.onload = function (e) {
                previewElement.src = e.target.result;
                previewElement.style.display = 'block';
                
            };

            reader.readAsDataURL(input.files[0]);
        }
    }



    // Trigger the function when a file is selected.
    let imageCoverInput = document.getElementById("image_cover");
    let imagePreview = document.getElementById('image_preview');
    let removeImageButton = document.getElementById("remove_image");
    // let song_file = document.getElementById('song_file');
    let removeSongButton = document.getElementById("remove_song_file");

    // Debug
    // document.getElementById("remove_image").classList.add("hidden");
    // console.log(document.getElementById('remove_image'));
    
    // Anonymouys function to remove the image.
    if (imageCoverInput) {
        imageCoverInput.addEventListener("change", function () {
            document.getElementById("remove_image").className = "display";
            readURL(this, imagePreview, removeImageButton);
        });
    }

    if(removeImageButton) {
        removeImageButton.addEventListener("click", () => {
            document.getElementById("remove_image").className = "hidden";
        })
    }

    // Anonymous function to remove audio file.
    if(removeSongButton) {
        removeSongButton.addEventListener("click", () => {
            document.getElementById("song_file").value = '';
            document.getElementById("remove_song_file").className = "hidden";
        });
    }
    
});
