
// function mutClick() {
//     var start = 1000;
//     var end = 2000; 
//     localStorage.setItem("start",start);
//     localStorage.setItem("end",end);
    
//     document.location.href = './Mutatio.html';
    
// // }
// function mutClick() {
//     var start = <?php echo $cov2Start ?>;
//     var end = <?php echo $cov2End ?>; 
//     localStorage.setItem("start",start);
//     localStorage.setItem("end",end);
    
//     document.location.href = './MutationSearch.php';
    
    // }

// console.log("hello");
// let mutClickbtns = document.querySelectorAll(".mutClick-btns");
// mutClickbtns.forEach(btn, (btn)=>{
//     btn.addEventListener("click",mutClick);
// })
function mutClick(btn) {
    console.log(btn.parentNode.parentNode.children[2].innerText);
    console.log(btn.parentNode.parentNode.children[4].innerText);
    var startEnd = btn.parentNode.parentNode.children[2].innerText;
    var startEdArray = startEnd.split("-")
    // var start = 
    // var end = btn.parentNode.parentNode.children[4].innerText; 
   
    document.location.href = "./MutationsSearch.php?start=" + startEdArray[0] + "&end=" + startEdArray[1];
    
    }