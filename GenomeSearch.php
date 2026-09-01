<!DOCTYPE html>
<html lang="en">
<head>
    <title>SARS-CoV2 Page</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <?php include "./Navigation.php";?>
    <?php include "./ProtienInfo.php";?>
    
    
    <style>
        .btn 
        {
          margin-top: 25px;
          margin-right: 5px;
        }
        .search-header 
        {
          padding-left: 10px;
        }
        .search-body
        {
          background-color: lightgrey;
        }
        .panel-body
        {
          background-color: white;
        }
        .comp-table-header {
          background-color: #3c3b3b;
          color: white;
        }
        .comp-table-header-td {
          padding: 5px;
          text-align: center;
          font-size: 13px;
          padding-right: 5px;
          border-right: 1px solid #a7a7a7;
        } 

        
    </style>
    <?php 
    if(isset($_GET['start'])){
      //print_r($_POST['proteinData']);
      $startData = $_GET['start'];
      $startExternal = true;
    }else{
      $startData = "";
      $startExternal = 1;
    }
    if(isset($_GET['end'])){
      //print_r($_POST['proteinData']);
      $endData = $_GET['end'];
      $endExternal = true;
    }else{
      $endData = "";

    }
  ?>
    <script>
      
      var clearButton = document.getElementById("clear_btn");
      //clearButton.addEventListener("click", resetForm);
      var proteinInfo = null;
      var parsedProteinData;
      var proteinSeqImgDiv;
      
    

      function getCovCov2ComparisonData() {
        var protein = document.getElementById("proteininner").innerText
        console.log(protein);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {   

              getCovAndCov2ComparisonPage(this.responseText);
            }
          }
          xmlhttp.open("GET", "GenomeComparisonData.php?Protein="+protein, true);
          xmlhttp.send();
      }

      function getCovAndCov2ComparisonPage(data) {
        // console.log(data)
          var protein = document.getElementById("Protein").value;
          var xmlHttpHtmlPost = new XMLHttpRequest();
          xmlHttpHtmlPost.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {   
              dataview.innerHTML = this.responseText;
              // console.log(data);
              var parsedData = JSON.parse(data);
              // console.log(parsedData[0].sars1seq)
                    
              // console.log(parsedData);
              var wdw = window.innerWidth;
              // console.log(wdw);
              var sars1js = parsedData[0].sars1seq;
              var sars2js = parsedData[0].sars2seq;
              var sarsevaljs = parsedData[0].sarsevalseq;
              // console.log(sarsevaljs);
              // sarsevaljsNewspaces = sarsevaljs.replace(" ","&nbsp;")
              var i=0;
              const cov2Array = Array();
              const cov1Array = Array();
              
              
              while (i<parsedData.length){
                // get the st and eds in separate arrays. 
                var tmpsted1 = parsedData[i].dashRange;
                splitsted1 = tmpsted1.split("-");

                // if (typeof(cov1Array[0][0]) === "string" ){
                // cov1Array[0] = "";
                // cov1Array[1] = "";
                // }

                cov1Array.push([parseInt(splitsted1[0])-1,parseInt(splitsted1[1])]);
                var tmpsted2 = parsedData[i].dashRange2;
                tmpJoin = tmpsted2.split("-")
                cov2Array.push([parseInt(tmpJoin[0])-1,parseInt(tmpJoin[1])]);
                i++
              }

              var space = wdw-50;
              // console.log(typeof sars1js)
              var par = sars2js.length;

              var outhtml = "";
              var indcount = 1;
              
              var divnode = document.getElementById('protein-sequence');

              var interval = 80;
              // below 3 track if it is the first line or not.  Separate from array 0 due to multiple domains in a line
              var start1 = true;
              var start2 = true;
              var evalst = true;

              var edval =0;
              var cov1ArrayCount = 0;
              var cov2ArrayCount = 0;
              var continued1 = false;
              var continued2 = false;
              var count1=0;
              var count2=0;
          

              const colorli = [ "#4F73FF", "#FF4F1B","#4FFF66", "#FD7CF1", "#F3FF00", "#FF54C3", "#FEA600" , "#F6FE00","#FE00B2","#7CFD8C","#00E2FF","#FF57D3", "#DDFFB5", "#B964FF", "#FFCD64","#64FFC3" ];
              // var newEntry1 = document.createElement('p');
              
              while (par>indcount){
                if (start1 || start2 || evalst){
                  var interval = 79;
                }else{
                  var interval = 80;
                }
                
                if (continued1){
                  if (cov1Array[cov1ArrayCount]== undefined){
                    continued1=false
                  }
                  console.log(cov1Array[cov1ArrayCount]);
              
                }
                

                if (!(cov1ArrayCount >= cov1Array.length) && continued2==false){
                    
                  if (continued1===false & continued2===false ){
                    var finArray1 = ['SARS-CoV   \xa0\xa0'];
                    var newEntry1 = document.createElement('p');
                    textContent1 = document.createTextNode(`SARS-CoV \xa0\xa0\xa0`);
                    newEntry1.appendChild(textContent1);
                  } 
                  try{
                    // console.log(cov1Array[cov1ArrayCount][0])
                  // console.log(cov1Array[cov1ArrayCount][1])
                  } catch (error){
                    // console.log(error)
                  }
                  

                  if (indcount <= cov1Array[cov1ArrayCount][0] && cov1Array[cov1ArrayCount][0] <= indcount+interval ) {
                    // regular start to domain start    
                    if (continued1===false || isNaN(cov1Array[cov1ArrayCount-1][0])  ){

                      if(start1=== true){
                        if( cov1Array[cov1ArrayCount][0]==1){
                          // pass
                        }else{
                        finArray1.push(sars1js.slice(indcount-1, cov1Array[cov1ArrayCount][0])); 
                        start1 = false;
                        }
                      }else{
                        finArray1.push(sars1js.slice(indcount, cov1Array[cov1ArrayCount][0])); 
                      }
                      // start1 = false;
                    } else{
                      finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount-1][1], cov1Array[cov1ArrayCount][0])); 
                    }
                    

                    finArray1.push(`<span style="background-color:${colorli[cov1ArrayCount]}">`) ; 
                    
                    if (indcount <= cov1Array[cov1ArrayCount][1] && cov1Array[cov1ArrayCount][1] <= indcount+interval){
                      // if end is in interval
                      
                      finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][0], cov1Array[cov1ArrayCount][1]));// domain start to minimum of end of interval or doamin end

                    
                      finArray1.push(`</span>`);
                      
                      
                      try{ if ((indcount <= cov1Array[cov1ArrayCount+1][0] && cov1Array[cov1ArrayCount+1][0] <= indcount+interval) || (indcount <= cov1Array[cov1ArrayCount+1][1] && cov1Array[cov1ArrayCount+1][1] <= indcount+interval ) ){
                        // if next domain is in this interval  restart looop but don't restart array
                        
                        
                        continued1 = true ;
                        cov1ArrayCount ++;
                        continue
                        // restartes while loop to add new domain in

                      }else{
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][1], indcount+interval));
                        cov1ArrayCount ++;
                      }
                      } catch (error) {
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][1], indcount+interval));
                        // cov1ArrayCount ++;
                        continued1 = false
                      }   
                      cov1ArrayCount ++;
                      continued1 = false;
                    } else {
                        // end is not in interval but start is and no other domains are
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][0],  indcount+interval)); // go to end of interval 
                        finArray1.push(`</span>`);
                        continued1 = false;
                        
                      } 

                    } else if (indcount <= cov1Array[cov1ArrayCount][1] && cov1Array[cov1ArrayCount][1] <= indcount+interval) {
                      // else if only the end is in interval
                      finArray1.push(`<span style="background-color:${colorli[cov1ArrayCount]}">`);
                      if(start1 === true){
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][0], cov1Array[cov1ArrayCount][1]));

                      }else{
                          finArray1.push(sars1js.slice(indcount, cov1Array[cov1ArrayCount][1])); 
                          start1 = false;

                        }
                      
                        
                        // if(start1=== true){
                        // console.log(cov1Array[cov1ArrayCount][0]);
                        // if( cov1Array[cov1ArrayCount][0]==1){
                        //   // pass
                        //   console.log("___________________passed")
                        //   finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][0]-1, cov1Array[cov1ArrayCount][1]));
                          
                        // }else{
                        // finArray1.push(sars1js.slice(indcount, cov1Array[cov1ArrayCount][1])); 
                        
                        // }
                      start1 = false;
                      
                      // finArray1.push(`<span style="background-color:${colorli[cov1ArrayCount]}">`);
                      // finArray1.push(sars1js.slice(indcount, cov1Array[cov1ArrayCount][1]));
                      finArray1.push(`</span>`);
                      // newEntry1.appendChild(x);
                      
                      
                      try{
                      if ((indcount <= cov1Array[cov1ArrayCount+1][0] && cov1Array[cov1ArrayCount+1][0] <= indcount+interval) || (indcount <= cov1Array[cov1ArrayCount+1][1] && cov1Array[cov1ArrayCount+1][1] <= indcount+interval )){
                        
                        // restarts while loop to add new domain in

                        
                        continued1 = true ;
                        cov1ArrayCount ++;
                        continue
                      }else{
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][1], indcount+interval));
                        cov1ArrayCount ++;
                        continued1 = false;
                      }
                      } catch (error) {
                        
                        
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][1], indcount+interval));
                        continued1 = false;
                      }
                      

                    } else if ( (cov1Array[cov1ArrayCount][0] <= indcount) && (indcount+interval <= cov1Array[cov1ArrayCount][1]) ){
                        // start and end is not in interval, check if intervals are smaller than st end 
                        finArray1.push(`<span style="background-color:${colorli[cov1ArrayCount]}">`);
                        if(start1 === true){
                        finArray1.push(sars1js.slice(cov1Array[cov1ArrayCount][0],indcount+interval));
                        start1 = false;

                      }else{
                          finArray1.push(sars1js.slice(indcount, indcount+interval)); 
                          start1 = false;

                        }
                        finArray1.push(`</span>`);
                        
                        
                        continued1 = false;
                        
                    }else if (isNaN(cov1Array[cov1ArrayCount][0])){
                      // this is for cases with no alignment and start and end are both 0 0 
                      cov1ArrayCount ++;
                      continued1 = true;
                      if (cov1Array[cov1ArrayCount]== undefined){
                        continued1=false
                        if(start1){
                          finArray1.push(sars1js.slice(indcount-1 ,  indcount+interval));
                          start1=false;
                        }else{
                          finArray1.push(sars1js.slice(indcount,  indcount+interval));

                        }
                      }else{
                        continue
                      }
                      
                    }else{
                      
                      
                      if(start1){
                          finArray1.push(sars1js.slice(indcount-1,  indcount+interval));
                          start1=false;
                        }else{
                          finArray1.push(sars1js.slice(indcount,  indcount+interval));
                        }
                    } 

                } else if (!continued2){
                  var finArray1 = ['SARS-CoV   \xa0\xa0'];
                  var newEntry1 = document.createElement('p');
                  textContent1 = document.createTextNode(`SARS-CoV \xa0\xa0\xa0`);
                  newEntry1.appendChild(textContent1);
                  finArray1.push(sars1js.slice(indcount,  indcount+interval))
                }

                //////////////////////////////COV2/////////////////////////////
                
                    
                if (!(cov2ArrayCount >= cov2Array.length)){
                    
                    if (continued2==false & continued1==false ){
                      var finArray2 = ['SARS-CoV-2   '];
                      var newEntry2 = document.createElement('p');
                      textContent2 = document.createTextNode(`SARS-CoV-2 \xa0`);
                      newEntry2.appendChild(textContent1);
                    } 


                    try{
                    //   console.log(cov2Array[cov2ArrayCount][0])
                    // console.log(cov2Array[cov2ArrayCount][1])
                    } catch (error){
                    }
                    
                    if (indcount <= cov2Array[cov2ArrayCount][0] && cov2Array[cov2ArrayCount][0] <= indcount+interval ) {
                      // console.log(cov2Array[cov2ArrayCount][0]);
                      // regular start to domain start    
                      if (!continued2){
                        if (start2){
                          finArray2.push(sars2js.slice(indcount-1, cov2Array[cov2ArrayCount][0])); 

                        }else{
                          finArray2.push(sars2js.slice(indcount, cov2Array[cov2ArrayCount][0])); 

                        }
                        start2= false;
                      } else{
                        finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount-1][1], cov2Array[cov2ArrayCount][0])); 
                        // console.log(sars2js.slice(cov2Array[cov2ArrayCount-1][1], cov2Array[cov2ArrayCount][0]));
                        // console.log("above is if continued2= true regular start ot domain")
                      }
                      
  
                      finArray2.push(`<span style="background-color:${colorli[cov2ArrayCount]}">`) ; 
                      
                      if (indcount <= cov2Array[cov2ArrayCount][1] && cov2Array[cov2ArrayCount][1] <= indcount+interval){
                        // if end is in interval
                        finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][0], cov2Array[cov2ArrayCount][1]));// domain start to minimum of end of interval or doamin end
                        finArray2.push(`</span>`);
                        continued2 = false;
                        
                        try{ if ((indcount <= cov2Array[cov2ArrayCount+1][0] && cov2Array[cov2ArrayCount+1][0] <= indcount+interval) || (indcount <= cov2Array[cov2ArrayCount+1][1] && cov2Array[cov2ArrayCount+1][1] <= indcount+interval ) ){
                          // if next domain is in this interval  restart looop but don't restart array
                          // console.log(cov2Array[cov2ArrayCount][0]);
                          // console.log(cov2Array[cov2ArrayCount+1][0]);
                          // console.log(indcount+interval);
                          
                          // console.log("2 continued");
                          
                          continued2 = true ;
                          cov2ArrayCount ++;
                          // console.log("2 end in interval but also new domain in interval");

                          continue
                          // restartes while loop to add new domain in
  
                        }else{
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][1], indcount+interval));
                          // cov2ArrayCount ++;
                          // console.log("2 end in interval but also NO new domain in interval");
                          // if (continued2 != true){
                          //   continued2=false
                          // } 
                          continued2 = false
                        }
                        } catch (error) {
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][1], indcount+interval));
                          // console.log(error);
                          continued2 = false;
                        }   
                        cov2ArrayCount ++;
                        // continued2 = false;
                      } else {
                          // end is not in interval but start is and no other domains are
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][0],  indcount+interval)); // go to end of interval 
                          finArray2.push(`</span>`);
                          // console.log("2 end is not in interval but start is not and no other domains are");
                          continued2 = false;
                          
                        } 
  
                      } else if (indcount <= cov2Array[cov2ArrayCount][1] && cov2Array[cov2ArrayCount][1] <= indcount+interval) {
                        // else if only the end is in interval


                        finArray2.push(`<span style="background-color:${colorli[cov2ArrayCount]}">`);
                        if(start2 === true){
                          // console.log(cov2Array[cov2ArrayCount][0]);
                          // console.log("___________________passed");
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][0], cov2Array[cov2ArrayCount][1]));

                        }else{
                            finArray2.push(sars2js.slice(indcount, cov2Array[cov2ArrayCount][1])); 
                            start2 = false;

                          }
                        
                        
                        start2 = false;
                        
                        
                        finArray2.push(`</span>`);
                        // console.log("end is in interval");
                        continued2 = false;
                        
                      
                        try{
                        if ((indcount <= cov2Array[cov2ArrayCount+1][0] && cov2Array[cov2ArrayCount+1][0] <= indcount+interval) || (indcount <= cov2Array[cov2ArrayCount+1][1] && cov2Array[cov2ArrayCount+1][1] <= indcount+interval )){
                          
                          // restarts while loop to add new domain in
  
                        
                          continued2 = true ;
                          cov2ArrayCount ++;
                          // console.log(" 2 end in interval but also new domain in interval");
                          continue
                        }else{
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][1], indcount+interval));
                          // cov2ArrayCount ++;
                          // console.log("2 only end in interval, NO new domain");
                          continued2 = false ;
                        }
                        } catch (error) {
                          
                          
                          finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][1], indcount+interval));
                          continued2 = false;
                        }
                        continued2 = false ;
                        cov2ArrayCount ++;
                      } else if ( (cov2Array[cov2ArrayCount][0] <= indcount) && (indcount+interval <= cov2Array[cov2ArrayCount][1]) ){
                          // start and end is not in interval, check if intervals are smaller than st end 
                          finArray2.push(`<span style="background-color:${colorli[cov2ArrayCount]}">`);
                          if(start2 === true){
                            // console.log(cov2Array[cov2ArrayCount][0]);
                            // console.log("___________________passed")
                            finArray2.push(sars2js.slice(cov2Array[cov2ArrayCount][0], indcount+interval));

                          }else{
                              finArray2.push(sars2js.slice(indcount, indcount+interval)); 
                              start2 = false;

                          }
                        
                        
                          start2 = false;
                          // finArray2.push(`<span style="background-color:${colorli[cov2ArrayCount]}">`);
                          // finArray2.push(sars2js.slice(indcount, indcount+interval)); // go to end of interval 
                          finArray2.push(`</span>`);
                          // console.log("2 start and end is not in interval, but intevral is in domain");
                        //  
                          
                          
                          continued2 = false;
                          
                      }else{
                        console.log("2 else statement, just interval");

                        if(start2){
                          finArray2.push(sars2js.slice(indcount-1,  indcount+interval));
                          start2 = false
                        }else{
                          finArray2.push(sars2js.slice(indcount,  indcount+interval));
                        }
                      
                        continued2 = false;
                      } 
  
                  }else{
                    
                    var finArray2 = ['SARS-CoV-2   '];
                    var newEntry2 = document.createElement('p');
                    textContent2 = document.createTextNode(`SARS-CoV-2 \xa0`);
                    newEntry2.appendChild(textContent1);
                    
                    // console.log("2 BIG else statement, just interval");

                    finArray2.push(sars2js.slice(indcount,  indcount+interval));
                    continued2 = false;
                  }
                


                
                
                var cov1join = finArray1.join("");
                var cov2join = finArray2.join("");

                cov1Joinbg = cov1join.replace("background-","");
                count1 += sars1js.slice(indcount, indcount+interval).replaceAll("-", "").length;
                continued1= false;
                continued2 = false;
                cov2Joinbg = cov2join.replace("background-","");
                count2 += sars2js.slice(indcount, indcount+interval).replaceAll("-", "").length;
//               This takes care of accurate line counts 
                if (evalst){
                  var tmpEval = sarsevaljs.slice(indcount-1,indcount +interval);
                  evalst=false;
                  // console.log("evalst is true");
                }else{
                  var tmpEval = sarsevaljs.slice(indcount,indcount +interval);
                }

                // tmpEval = "<pre>" +tmpEval + "<pre>"
                // var tmpEval = sarsevaljs.slice(indcount-1,indcount +interval);
                var blockDiv = document.createElement('div');

                indcount += interval;   
                textContent1 = document.createTextNode(`   ${count1}`);
                
                newEntry1.innerHTML = cov1join;
                newEntry1.appendChild(textContent1);
                newEntry1.style["margin"] = 0;

                textContent2 = document.createTextNode(`   ${count2}`);
                newEntry2.innerHTML = cov2join;
                newEntry2.appendChild(textContent2);
                newEntry2.style["margin"] = 0;

                var newEntryeval = document.createElement('p');
                tmpEval = tmpEval.replaceAll(" ","\xa0")

                textContenteval = document.createTextNode(`\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0${tmpEval} `);
                newEntryeval.appendChild(textContenteval);
                newEntryeval.style["margin"] = 0;
                
                blockDiv.appendChild(newEntry1);
                blockDiv.appendChild(newEntry2);
                blockDiv.appendChild(newEntryeval);
                
                
                divnode.appendChild(blockDiv);
                newPageTitle = "SARS-CoV-2 and Sars-CoV comparison page";
                document.querySelector('title').textContent = newPageTitle;
                
                function mutClick() {
                  var start = parsedData[0].cov2Start;
                  var end = parsedData[0].cov2End; 
                  localStorage.setItem("start",start);
                  localStorage.setItem("end",end);
                  
                  document.location.href = './MutationSearch.php';
                  
                }
                 
                       
              }
  
            }
          }
          xmlHttpHtmlPost.open("POST", "GenomeComparison.php", true);
          xmlHttpHtmlPost.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xmlHttpHtmlPost.send('comparisonData=' + data + "&protein=" + protein);
      }

      function resetForm(){
          document.getElementById("Start").value="";
          document.getElementById("End").value="";
      }  
      function getGenomeData(getData) {
        // performed when Submit is hit
        // VERSION 2.0 - Added DNA sequence search support
        
        var proteinEl = document.getElementById("Protein");
        var startEl = document.getElementById("Start");
        var endEl = document.getElementById("End");
        if (!startEl || !endEl) { return; }
        var protein = proteinEl ? proteinEl.value : "";
        var start = startEl.value;
        var end = endEl.value;

        var dataview = document.getElementById("dataview");
        dataview.style.display="none";
        var searchgrid = document.getElementById("searchgrid");
        searchgrid.style.display="block";
        var datagrid = document.getElementById("datagrid");
        
        function containsAny(str) {
          if (str.length==0){
            return false;
          }
          for (var i = 0; i < str.length; i++) {
            var substring = str[i];

          
            if (['1', '2', '3','4','5','6','7','8','9','0'].includes(substring)) {
              console.log(substring);
              continue;
            }else{
              return false;
            }
            } 
            return true; 
            }
        
        function isDNASequence(str) {
          if (str.length==0){
            return false;
          }
          // Check if string contains only DNA bases (A, T, G, C) - case insensitive
          // Updated: Now allows DNA sequence input (v2.0)
          var dnaPattern = /^[ATGCatgc]+$/;
          return dnaPattern.test(str);
        }
        
          // Allow DNA sequences OR numeric coordinates
          var startIsSequence = isDNASequence(start);
          var endIsSequence = isDNASequence(end);
          var startIsNumeric = containsAny(start);
          var endIsNumeric = containsAny(end);
          
          // If start is provided and it's neither sequence nor numeric, show error
          if (start != "" && !startIsSequence && !startIsNumeric) {
            alert("Please input a valid start: either a DNA sequence (A, T, G, C) or numeric coordinates.");
            return;
          }
          
          // If end is provided and it's neither sequence nor numeric, show error
          if (end != "" && !endIsSequence && !endIsNumeric) {
            alert("Please input a valid end: either a DNA sequence (A, T, G, C) or numeric coordinates.");
            return;
          }
          
          // Proceed if: sequences are detected, or numeric values are detected, or both fields are empty
          if (startIsSequence || endIsSequence || startIsNumeric || endIsNumeric || (start == "" && end == "")){
        if(getData === true) {
          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {            
              datagrid.innerHTML = this.responseText;
              
            }
          }
          
          xmlhttp.open("GET", "GenomeResult.php?Protein="+encodeURIComponent(protein)+"&Start="+encodeURIComponent(start)+"&End="+encodeURIComponent(end), true);
          
          xmlhttp.send();

          
        
        }}
        
      }
      function mutSearch() {

        var protein = document.getElementById("Protein").value;
        var start = document.getElementById("Start").value;
        var end = document.getElementById("End").value;

        if (start != "" && end != ""){
          // localStorage.setItem("start",start);
          // localStorage.setItem("end",end);
          
          // console.log(localStorage.getItem('start'));
          // console.log(localStorage.getItem('end'));
          // document.location.href = './MutationsSearch.php';
          document.location.href = "./MutationsSearch.php?start=" + start + "&end=" + end;


        }
        if (start != "" && end == ""){
          document.location.href = "./MutationsSearch.php?start=" + start;
        }
        if (start == "" && end != ""){
          document.location.href = "./MutationsSearch.php?end=" + end;
        }
        if (start == "" && end == "" && protein != null){
          document.location.href = "./MutationsSearch.php?protein=" + protein;
        }
      }

      // function mutationLink(boo){
      //   var protein = document.getElementById("Protein").value;
      //   var start = document.getElementById("Start").value;
      //   var end = document.getElementById("End").value;


      //   document.getElementById("myButton").onclick = function () {
      //   location.href = "www.yoursite.com";
      //  };
      // }
      function getGeneDetails(gene, protein) {
        console.log(gene,protein);
        $proteinData = '';
        if(gene !== "" || protein !== "") {
          var dataview = document.getElementById("dataview");
          dataview.style.display="block";
          var searchgrid = document.getElementById("searchgrid");
          searchgrid.style.display="none";

          //post the 
          var xmlHttpHtmlPost = new XMLHttpRequest();
          xmlHttpHtmlPost.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {            
              // console.log(this.responseText);
              dataview.innerHTML = this.responseText;
              
              var s = document.createElement('script');
              s.type = 'text/javascript';
              s.src = './JS/domainPlotter5.js';
              try {
                document.body.appendChild(s);
              } catch (e) {
                document.body.appendChild(s);
              }

              proteinImageDiv = document.getElementById('protein-image-div');
              if(proteinImageDiv)
                  proteinImageDiv.innerHTML = proteinInfo.imageTag;

            }
          }
          xmlHttpHtmlPost.open("POST", "GenomeDetail.php", true);
          
          xmlHttpHtmlPost.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          newPageTitle = protein + " gene detail page";
          document.querySelector('title').textContent = newPageTitle;


          var xmlHttpGetData = new XMLHttpRequest();
          xmlHttpGetData.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
              //$proteinData = 'proteinData='+this.responseText;
              // console.log(this.responseText);
              proteinInfo = JSON.parse(this.responseText);
              parsedProteinData = proteinInfo.detailInfo;
              $proteinData = 'proteinData=' + JSON.stringify(proteinInfo.detailInfo);
              xmlHttpHtmlPost.send($proteinData)
              // console.log(parsedProteinData);
              // console.log(proteinInfo,"...");
            }
          }
          xmlHttpGetData.open("GET", "GenomeDetailData.php?Gene="+gene+"&Protein="+protein, true);
          xmlHttpGetData.send();
        }
      }

      function copyFunction(prot,seq) {
        // Get the text field
        var copyText = ">"+prot+"\n"+seq;

        // Select the text field
        // copyText.select();
        // copyText.setSelectionRange(0, 99999); // For mobile devices

        // Copy the text inside the text field
        navigator.clipboard.writeText(copyText);

        // Alert the copied text
        alert("Copied "+prot+" to clipboard, redirecting to SNAP2 webpage");
        document.location.href = "https://rostlab.org/services/snap2web/";
      }
    </script>
</head>

<body class="search-body">
  <h4 class="search-header">Genome</h4> 
  <div class="panel-body">
    
      
    <div id="searchgrid">         
      <div class="form-group" style="height:10%; width:100%;">
        <div class="row">
          <fieldset id="Gene_row">
            
            <div class="col-md-2">
                <label for="Start">Start</label>
                <input id="Start" type="text" class="form-control" id="Start" value="<?php echo htmlspecialchars($startData);?>" />
            </div>
            <div class="col-md-2">
                <label for="End">End</label>
                <input id="End" type="text" class="form-control" id="End" value="<?php echo htmlspecialchars($endData); ?>"/>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success" id="submit_btn" name="submit" onclick="getGenomeData(true)">Submit</button>
                <button type="submit" class="btn btn-success" id="mut_btn" name="Mutation" onclick="mutSearch()">Search Mutations</button>
                <button type="button" onclick="resetForm();" id="clear_btn" class="btn btn-secondary">Clear</button>
            </div>
            <script> if (<?php echo $startExternal; ?>  == true){
              function runGenomeData(){ getGenomeData(true); }
              if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", runGenomeData);
              } else { runGenomeData(); }
            } </script>
            <div class="col-md-2">
              <label for="Protein">Gene/Protein</label>
              <select class = "form-control" id="Protein" onchange="getGenomeData(true)">
                <option value= "">All</option>
                <option value="Nsp1">Nsp1</option>
                <option value="Nsp2">Nsp2</option>
                <option value="Nsp3">Nsp3</option>
                <option value="Nsp4">Nsp4</option>
                <option value="Nsp5">Nsp5</option>
                <option value="Nsp6">Nsp6</option>
                <option value="Nsp7">Nsp7</option>
                <option value="Nsp8">Nsp8</option>
                <option value="Nsp9">Nsp9</option>
                <option value="Nsp10">Nsp10</option>
                <!-- <option value="Nsp11">Nsp11</option> -->
                <option value="Nsp12">Nsp12</option>
                <option value="Nsp13">Nsp13</option>
                <option value="Nsp14">Nsp14</option>
                <option value="Nsp15">Nsp15</option>
                <option value="Nsp16">Nsp16</option>
                <option value="Surface Glycoprotein">S Gene</option>
                <option value="ORF3a Protein">ORF3a</option>
                <option value="Envelope Membrane Protein">E Gene</option>
                <option value="Membrane Protein">M Gene</option>
                <option value="ORF6 Protein">ORF6</option>
                <option value="ORF7a Protein">ORF7a</option>
                <!-- <option value="ORF7b Protein">ORF7b</option> -->
                <option value="ORF8 Protein">ORF8</option>
                <option value="ORF9b protein">ORF9b</option>
                <option value="Nucleocapsid proteins">N Gene</option>
                <!-- <option value="ORF10 Protein">ORF10</option> -->
              </select>
            </div>

          </fieldset>
          <br />
      </div>
      </div>
      <div id="datagrid" style="height:90%; width:100%;">
        <i>Apply filters and hit submit to start seeing results.</i>
      </div>
    </div>
    <div id="dataview" style="height:90%; width:100%;"></div>
  </div>
  <?php if (isset($_GET['Protein'])){
        // echo "<h4>Test hello</h4>";
        $protein = $_GET['Protein'];
        $gene = $_GET['Gene'];
        $datadetails = "";
        $datadetails .= "<script> getGeneDetails('$gene','$protein');";
        $datadetails .= "</script>" ;
        echo $datadetails;
      } ?>
  <script src="./JS/mutClick.js"></script>
</body>

</html>
