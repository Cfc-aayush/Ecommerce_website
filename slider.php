<style>
    #slider {
        width: 100%;
        height: 700px; /* Adjust the height as needed */
        overflow: hidden;
        position: relative; 
    }

    #slider figure {
        display: flex;
        width: 500%; /* Adjust based on the number of images */
        margin: 0;
        padding: 0;
        transition: transform 4s ease-in-out; /* Increase the animation time to 4 seconds */
    }

    #slider figure img {
        width: 20%; /* Keep the photo size the same */
        height: 700px;
        object-fit: cover;
    }
</style>
<div id="slider">
    <figure>
        <img src="photos(project)/other/img7.png" alt="Slide 1">
        <img src="photos(project)/other/img1.png" alt="Slide 2">
        <img src="photos(project)/other/img2.png" alt="Slide 3">
        <img src="photos(project)/other/img4.png" alt="Slide 4">
        <img src="photos(project)/other/img7.png" alt="Slide 1"> <!-- Duplicate the first image -->
    </figure>
</div>
<script>
    const slider = document.getElementById('slider');
    const figure = document.querySelector('#slider figure');
    
    let counter = 1;
    const size = 20; // 100% / number of images

    function transitionEndHandler() {
        if (counter >= 5) {
            figure.style.transition = 'none';
            figure.style.transform = 'translateX(0)';
            setTimeout(() => {
                figure.style.transition = 'transform 4s ease-in-out'; /* Increase the animation time to 4 seconds */
                counter = 1; // Reset the counter to the first slide
            }, 50);
        }
    }

    figure.addEventListener('transitionend', transitionEndHandler);

    setInterval(() => {
        figure.style.transform = `translateX(${-size * counter}%)`;
        counter++;
    }, 5000); // Change slide every 5 seconds
</script>
