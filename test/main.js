console.log("hello");
const box = document.getElementById("box1");
const motifd = document.getElementById("motif")
console.log(motifd)
const textbox = document.getElementById("textoutput")
// if (motif === )

var length = 8;
var nums = [70,21555,25384,26236,26472,27040,27388,27887,28259];

function map(num) {
  var total = 29903;
  var box_length = 900;
  return (num * box_length) / total; //margin-left
}

// highlight.style.marginLeft = map(11000) + "px";
// highlight2.style.marginLeft = map(14000) + "px";


var genests = [89,
  21563,
  25393,
  26245,
  26523,
  27202,
  27394,
  27756,
  27894,
  28274,
  29558]

var geneed = [21552,
  25384,
  26220,
  26472,
  27191,
  27387,
  27759,
  27887,
  28259,
  29533,
  29674]

  // ["233d4d","915e3d","fe7f2d","fcca46","cfc664","a1c181","81ae86","71a588","69a089","619b8a"]
var genecolor =["233d4d","3F4649","5a4e45","915e3d","fe7f2d","fda53a","fcca46","cfc664","a1c181","71a588","619b8a"]


for (let i = 0; i < genests.length; i++) {
  var highlight = document.createElement("div");
  highlight.classList.add("genehighlight");
  box.appendChild(highlight);
  highlight.style.background = "#"+ genecolor[i];
  highlight.style.width = map(geneed[i]) - map(genests[i]) +"px";
  highlight.style.marginLeft = map(genests[i]) + "px";
  console.log(map(geneed[i]) - map(genests[i]) +"px")
}

function plotrepeats(array)
{
  var motifi = document.getElementById("motif").value

  // console.log(motifinput)
  // motifd = document.getElementById("motif")
  for (let i = 0; i < genests.length; i++) {
    var highlight = document.createElement("div");
    highlight.classList.add("genehighlight");
    box.appendChild(highlight);
    highlight.style.background = "#"+ genecolor[i];
    highlight.style.width = map(geneed[i]) - map(genests[i]) +"px";
    highlight.style.marginLeft = map(genests[i]) + "px";
    console.log(map(geneed[i]) - map(genests[i]) +"px")
  }

  // this creates the motifs on the diagram
  for (let i = 0; i < array.length; i++) {
    var highlight = document.createElement("div");
    highlight.classList.add("highlight");
    box.appendChild(highlight);
    highlight.style.marginLeft = map(array[i]) + "px";
  }
// // THis gives motif counts 
//   var textContent  = document.createElement("p");
//   var numl = nums.length
//   textContent.innerText= "Repeat Count:" + nums.length;
  
//   textbox.appendChild(textContent);

//   // this give the list of coordinates\
//   var textContent  = document.createElement("p");
//   textContent.innerText= "Repeat Coordinates:";
//   textbox.appendChild(textContent);

//   for (let i = 0; i < nums.length; i++) {
//     var textContent = document.createElement("p");
//     textContent.innerText= nums[i]
//     textbox.appendChild(textContent)
//   }
  

  }



