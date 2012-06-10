
// functions for leitner play

function togglecard(){
    var questionobj = document.getElementById("questiondiv");
    var answerobj = document.getElementById("answerdiv");
    if (questionobj.style.display == "none"){
	    questionobj.style.display = "block";
	    playertype = 'dewplayer';
	    
	    // controls the quicktime player switching
	    answerobj.style.display = "none";
	    if (playertype != 'qtime'){
    	    if (atype >= 2){
        	    bellobj = document.getElementById("bell_a");
        	    bellobj.Stop();
        	    bellobj.SetControllerVisible(false);
        	}
    	    if (qtype >= 2){
        	    bellobj = document.getElementById("bell_q");
        	    bellobj.SetControllerVisible(true);
        	}
        }
	} else {
	    questionobj.style.display = "none";
	    answerobj.style.display = "block";
	    playertype = 'dewplayer';

	    // controls the quicktime player switching
	    if (playertype != 'qtime'){
    	    if (atype >= 2){
        	    bellobj = document.getElementById("bell_a");
        	    bellobj.SetControllerVisible(true);
        	}
    	    if (qtype >= 2){
        	    bellobj = document.getElementById("bell_q");
        	    bellobj.Stop();
        	    bellobj.SetControllerVisible(false);
        	}
        }
	}
}

// functions for freeplay

function clicked(type, item){
    document.getElementById(type + item).style.display = "none";
    shown = item;
    if (type == 'f'){
	    oldtype = 'b';
	} 
	else{
	    oldtype = 'f';
	}
    document.getElementById(oldtype + item).style.display = "block";
    if (type == 'f'){
        if (atype > 2){
            alert('item ' + item);
            qtobj = document.getElementById('bell_b' + item);
            qtobj.SetControllerVisible(true);
        }
    }
    if (type == 'b'){
        if (qtype > 2){
            qtobj = document.getElementById('bell_f' + item);
            qtobj.SetControllerVisible(true);
        }
    }
}

function next()
{
    document.getElementById('f' + currentitem).style.display = "none";
    document.getElementById('b' + currentitem).style.display = "none";
    do {
        currentitem++;
        if (currentitem >= maxitems) currentitem = 0;
    }
    while (cards[currentitem] != true){
        document.getElementById('f' + currentitem).style.display = "block";
        qtobj = document.getElementById('bell_f' + currentitem);
        qtobj.SetControllerVisible(true);
    }
}
        
function previous() {
    document.getElementById('f' + currentitem).style.display = "none";
    document.getElementById('b' + currentitem).style.display = "none";
	do {
        currentitem--;
        if(currentitem < 0) currentitem = maxitems - 1;
    }
    while (cards[currentitem] != true){
        document.getElementById('f' + currentitem).style.display = "block";
        qtobj = document.getElementById('bell_f' + currentitem);
        qtobj.SetControllerVisible(true);
    }
}
      
function remove(){
    remaining--;
    document.getElementById('remain').innerHTML = remaining;
    if (remaining == 0){
          document.getElementById('f' + currentitem).style.display = "none";
          document.getElementById('b' + currentitem).style.display = "none";
      	  document.getElementById('finished').style.display = "block";
      	  document.getElementById('next').disabled = true;
      	  document.getElementById('previous').disabled = true;
      	  document.getElementById('remove').disabled = true;
    }
    else{
          cards[currentitem] = false;
          next();
    }
}

function freetogglecard(){
    clicked(oldtype, currentitem);
}
