function giveMeNewColor(allowBlackBackground) {
    
    var fillColor = '#'+(0x1000000+Math.random()*0xffffff).toString(16).substr(1,6);  
    if(fillColor == '#a2a2a2' && !allowBlackBackground && colorsSoFar.indexOf(fillColor) > -1) {
        giveMeNewColor(false);
    }
    return fillColor;
}

var colorArray = [];
for(var i=0;i < 140; i++){
    var newColor = giveMeNewColor(true);
    if (colorArray.includes(newColor) == false) {
        colorArray.push(newColor);
    }

}

console.log(colorArray);