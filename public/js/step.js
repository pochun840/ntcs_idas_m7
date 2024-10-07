document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'copyButton') {
            copy_step_by_id();
        }
    });
});

function copy_step(stepid){
    document.getElementById('copystep').style.display = 'block';   
    copy_step_by_id();

}


function disableElements(elements, value) {
    elements.forEach(function(element) {
        element.disabled = value;
        element.value = value === true ? 0 : ''; 
    });
}