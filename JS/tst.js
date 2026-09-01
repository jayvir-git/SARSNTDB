const submit_btn= document.getElementById("submit_btn");
const datagrid = document.getElementById("datagrid");






submit_btn.addEventListener("click",function(){
	var st = document.getElementById("Start").value;
      var ed = document.getElementById("End").value;

      let result = func([parseInt(st),parseInt(ed)]);
      // console.log(result)
      for (let i= 0; i<result.length;i++){
            console.log(result[i])
            let entry1 = document.createTextNode(result[i][0]);
            let entry2= document.createTextNode(result[i][1]);
            let entrybox = document.createElement("div");
            let entry1text = document.createElement("p");
            entrybox.appendChild(entry1);
            entrybox.appendChild(entry2);
            datagrid.appendChild(entrybox);
            
      }
      
      
});

function callbackFunc(reponse){
      alert(response);
      console.log(response);
}

