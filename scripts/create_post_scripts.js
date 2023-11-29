
/*******w******** 
    
    @author: Nigel Abrera
    @date: 11/8/2023
    @description: Logic for previewing the page.

****************/
// BUGFIX: Made the remove button dissapear when there's no image or audio file to be 
document.addEventListener("DOMContentLoaded", load);


function load() {
    
    // Sets the button to be hidden.
    document.getElementById('remove_image').style.display = 'none';
    document.getElementById('remove_song_file').style.display = 'none';

    /**
     * Function to display image preview.
     * @param input File path 
     * */ 
    function readURL(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById('image_preview').src = e.target.result;
                document.getElementById('image_preview').style.display = 'block';
                document.getElementById('remove_image').style.display = 'block';
            };

            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Trigger the function when a file is selected.
    let imageCoverInput = document.getElementById("image_cover");
    if (imageCoverInput) {
        imageCoverInput.addEventListener("change", function () {
            // document.getElementById('remove_image').style.display = 'block';
            readURL(this);
        });
    }

    

    // Anonymouys function to remove the image.
    let removeImageButton = document.getElementById("remove_image");
    if(removeImageButton) {
        removeImageButton.addEventListener("click", () => {
            document.getElementById("image_preview").src = '#';
            document.getElementById('image_preview').style.display = 'none';
            document.getElementById('image_cover').value = '';
            removeImageButton.style.display = 'none';
        });
    }

    // Anonymous function to remove audio file.
    let removeSongButton = document.getElementById("remove_song_file");
    if(removeSongButton) {
        removeSongButton.addEventListener("click", () => {
            document.getElementById("song_file").value = '';
            document.getElementById("remove_song_file").style.display = 'none';
        });
    }
    
    let songFile = document.getElementById('song_file');
    if(songFile) {
        songFile.addEventListener("change", function () {
            document.getElementById("remove_song_file").style.display = 'block';
        });
    } 
}