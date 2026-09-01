// console.log("hello");
const box = document.getElementById("repeatDisplay");
const motifd = document.getElementById("motif")
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


var genests = [
  266,
  806,
  2720,
  8555,
  10055,
  10973,
  11843,
  12092,
  12686,
  13025,
  13442,
  13442,
  16237,
  18040,
  19621,
  20659,
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

var geneed = [
  805,
  2719,
  8554,
  10054,
  10972,
  11842,
  12091,
  12685,
  13024,
  13441,
  13480,
  16236,
  18039,
  19620,
  20658,
  21552,
  
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

var GENESDISPLAY= ["1",
  "2",
  "3",
  "4",
  "5",
  "6",
  "7",
  "8",
  "9",
  "10",
  "11",
  "12",
  "13",
  "14",
  "15",
  "16",
  "S",
  "3a",
  "E",
  "M",
  "6",
  "7a",
  "7b",
  "8",
  "N",
  "10"
  ]

  var GENESLINK= ["ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "ORF1ab",
  "S gene",
  "ORF3a",
  "E Gene",
  "M Gene",
  "ORF6",
  "ORF7a",
  "ORF7b",
  "ORF8",
  "N Gene",
  "ORF10"
  ]

  var PROTEINS= ["NSP1",
  "NSP2",
  "NSP3",
  "NSP4",
  "NSP5",
  "NSP6",
  "NSP7",
  "NSP8",
  "NSP9",
  "NSP10",
  "NSP11",
  "NSP12",
  "NSP13",
  "NSP14",
  "NSP15",
  "NSP16",
  "Surface Glycoprotein",
  "ORF3a Protein",
  "Envelope Membrane Protein",
  "Membrane Gene",
  "ORF6 Protein",
  "ORF7a Protein",
  "ORF7b Protein",
  "ORF8 Protein",
  "Nucleocapsid proteins",
  "ORF10 Protein"
  ]
  // ["233d4d","915e3d","fe7f2d","fcca46","cfc664","a1c181","81ae86","71a588","69a089","619b8a"]
var genecolor =["DAF7A6","FFC300","FF5733","C70039","900C3F" , "85929e","2596be","9925be","be4d25","49be25","d966ff","668cff","ff66b3","5a4e45","915e3d","fe7f2d","fda53a","fcca46","cfc664","a1c181","71a588","619b8a","0e6251","21618c","9b59b6","7d3c98"]


for (let i = 0; i < genests.length; i++) {
  console.log(i);
  var highlight = document.createElement("div");
  highlight.classList.add("genehighlight");
  box.appendChild(highlight);
  highlight.style.background = "#"+ genecolor[i];
  highlight.style.width = map(geneed[i]) - map(genests[i]) +"px";
  highlight.style.marginLeft = map(genests[i]) + "px";
  console.log(map(geneed[i]) - map(genests[i]) +"px")
}

function createLegend(){
  var LegendTable = document.createElement('table');

  
  trtop = document.createElement('tr');
  emptytd =document.createElement('td');

  trtop.classList.add("comp-table-header");
  tdnsp = document.createElement('td');
  tdSP = document.createElement('td');
  
  tdnsp.classList.add("comp-table-row-td");
  tdSP.classList.add("comp-table-row-td");
  emptytd.classList.add("comp-table-row-td");
  trtop.appendChild(emptytd);

  tdnsp.appendChild(document.createTextNode("Non-Structural Proteins"));
  tdSP.appendChild(document.createTextNode("Structural Proteins"));
  trtop.appendChild(tdnsp);

  trtop.appendChild(tdSP);
  tdnsp.colSpan = "16";
  tdnsp.style.width = "70%";
  tdnsp.align = "center";
  tdSP.colSpan = '10';
  tdSP.style.width = '30%';
  tdSP.align = "center";


  LegendTable.appendChild(trtop);


  tr = document.createElement('tr');
  td = document.createElement('td');
  tdsuperhead = document.createElement('td');
  tr.classList.add("comp-table-header-grey");
  td.classList.add("comp-table-row-td");
  td.appendChild(document.createTextNode("Gene"));
  tdsuperhead.appendChild(document.createTextNode("Color"));
 
  tr.appendChild(td);
  
  
  LegendTable.appendChild(tr);



  for (let i = 0; i < GENESLINK.length; i++) {
    
    td = document.createElement('td');
    td.classList.add("comp-table-row-td");
    // td.classList.add("comp-table-row-td");
    // td.classList.add("comp-table-header");
    var a = document.createElement('a');
    var linkText = document.createTextNode(GENESDISPLAY[i]);
    a.appendChild(linkText);
    a.title = PROTEINS[i];
    // var link = "./GenomeSearch.php?start=" + zippedArray[i][0];
    a.href = "./GenomeSearch.php?Gene=" + GENESLINK[i] + "&Protein=" + PROTEINS[i];
    td.appendChild(a);

    
    // var colorSpan = document.createElement('span');
    // colorSpan.classList.add("color-palette");
    // colorSpan.style.backgroundColor = genecolor[i]
    // tdColor = document.createElement('td');
    // tdColor.classList.add("comp-table-row-td");
    // tdColor.appendChild(colorSpan);


    tr.appendChild(td);
    // tr.appendChild(tdColor);
    // LegendTable.appendChild(tr);
  }
  LegendTable.appendChild(tr);
  trColor = document.createElement('tr');
  tdColor = document.createElement('td');
  tdColor.appendChild(document.createTextNode("Color"));
  trColor.appendChild(tdColor);

  LegendTable.appendChild(trColor);
  for (let i = 0; i < GENESLINK.length; i++) {

    var colorDiv = document.createElement('div');
    colorDiv.classList.add("color-palette");
    colorDiv.style.backgroundColor = "#"+ genecolor[i]
    tdColor = document.createElement('td');
    tdColor.classList.add("comp-table-row-td");
    tdColor.appendChild(colorDiv);
    trColor.appendChild(tdColor);


  }


  document.getElementById('legendRow').appendChild(LegendTable);
  


}
function plotrepeats(array)
{
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

  function resetFormGenome(){
    // console.log("clicked");
    container.innerHTML = '';
    var highlights = document.getElementsByClassName("highlight");
    var parentNode = document.getElementById("repeatDisplay");
    while(highlights.length>0){
      parentNode.removeChild(highlights[0])
      // highlights[0]
    }}
    
  function plotIntraGenome(array)
{
  function resetFormGenome(){
    // console.log("clicked");
    container.innerHTML = '';
    var highlights = document.getElementsByClassName("highlight");
    var parentNode = document.getElementById("repeatDisplay");
    while(highlights.length>0){
      parentNode.removeChild(highlights[0])
      // highlights[0]
    }}
  resetFormGenome()
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



