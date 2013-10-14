var cards = new Array(maxitems);
for(i = 0 ; i < maxitems ; i++){
    cards[i] = true;
}

function clicked(type, item){
    document.getElementById(type + item).style.display = "none";
    if (type == 'f'){
	    oldtype = 'b';
	} 
	else{
	    oldtype = 'f';
	}
    document.getElementById(oldtype + item).style.display = "table-cell";
    if (type == 'f'){
        if (atype > 2){
            qtobj = document.getElementById('bell_b' + item+'_player');
            qtobj.SetControllerVisible(true);
        }
    }
    if (type == 'b'){
        if (qtype > 2){
            qtobj = document.getElementById('bell_f' + item+'_player');
            qtobj.SetControllerVisible(true);
        }
    }
}

// Free play only

function next_card()
{
    document.getElementById('f' + currentitem).style.display = "none";
    document.getElementById('b' + currentitem).style.display = "none";
    do {
        currentitem++;
        if (currentitem >= maxitems) currentitem = 0;
    }
    while (cards[currentitem] != true){
        document.getElementById('f' + currentitem).style.display = "table-cell";
        if (qtobj = document.getElementById('bell_f' + currentitem+'_player')){
	        qtobj.SetControllerVisible(true);
	    }
    }
}
        
function previous_card() {
    document.getElementById('f' + currentitem).style.display = "none";
    document.getElementById('b' + currentitem).style.display = "none";
	do {
        currentitem--;
        if(currentitem < 0) currentitem = maxitems - 1;
    }
    while (cards[currentitem] != true){
        document.getElementById('f' + currentitem).style.display = "table-cell";
        if (qtobj = document.getElementById('bell_f' + currentitem+'_player')){
	        qtobj.SetControllerVisible(true);
	    }
    }
}
      
function remove_card(){
    remaining--;
    document.getElementById('remain').innerHTML = remaining;
    if (remaining == 0){
          document.getElementById('f' + currentitem).style.display = "none";
          document.getElementById('b' + currentitem).style.display = "none";
      	  document.getElementById('finished').style.display = "table-cell";
      	  document.getElementById('next').disabled = true;
      	  document.getElementById('previous').disabled = true;
      	  document.getElementById('remove').disabled = true;
    } else {
          cards[currentitem] = false;
          next_card();
    }
}

// Leitner play only

function togglecard(){
    var questionobj = document.getElementById("questiondiv");
    var answerobj = document.getElementById("answerdiv");
    if (questionobj.style.display == "none"){
	    questionobj.style.display = "table-cell";
	    
	    // controls the quicktime player switching
	    answerobj.style.display = "none";
	    if (atype >= 2){
    	    bellobj = document.getElementById('bell_a_player');
    	    bellobj.Stop();
    	    bellobj.SetControllerVisible(false);
    	}
	    if (qtype >= 2){
    	    bellobj = document.getElementById('bell_q_player');
    	    bellobj.SetControllerVisible(true);
    	}
	} else {
	    questionobj.style.display = "none";
	    answerobj.style.display = "table-cell";

	    // controls the quicktime player switching
	    if (atype >= 2){
    	    bellobj = document.getElementById('bell_a_player');
    	    bellobj.SetControllerVisible(true);
    	}
	    if (qtype >= 2){
    	    bellobj = document.getElementById('bell_q_player');
    	    bellobj.Stop();
    	    bellobj.SetControllerVisible(false);
    	}
	}
}

