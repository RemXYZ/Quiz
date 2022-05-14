const errorAnimationList = {}
//aKey = animation key this is unique key to avoid repeating this animation
function errorHighlightAnimation(el, newCSS, time, aKey){
    if (!errorAnimationList[aKey]) {
        errorAnimationList[aKey] = true;
    }else {
        return;
    }

    const lastElCSS = {};
    for( const [k,v] of Object.entries(newCSS)) {
        lastElCSS[k] = el.css(k);
    }
    el.css(newCSS);
    setTimeout(()=>{
        delete errorAnimationList[aKey];

        el.css(lastElCSS);
    }, time)
}


function checkUser() {

    const fName = $(".QuizFirstName");
    const lName = $(".QuizLastName");
    const testKey = $(".QuizKeyWord");


    return asa.ajax({
        url: urlRoot+"/src/database/userService/userConn.php",
        method:"POST",
        data:{
            fName:fName[0].value, 
            lName:lName[0].value, 
            testKey:testKey[0].value
        }
    })
    .then(respData=>{
        const resp = JSON.parse(respData);
        if (resp.error == "fNameError") {
            errorHighlightAnimation(fName,{border:"2px solid #d1160c"},1500, resp.error)
            return false;
        }
        else if (resp.error == "lNameError") {
            errorHighlightAnimation(lName,{border:"2px solid #d1160c"},1500, resp.error)
            return false;
        }
        else if (resp.error == "badKey") {
            errorHighlightAnimation(testKey,{border:"2px solid #d1160c"},1500, resp.error)
            return false;
        }
        else if (resp.error == "cookieError") {
            console.error("cookieError")
            window.location.reload();
            return false;
        }
        else if (resp.error) {
            console.error("check error")
            return false;
        }

        return resp;

    })

}


function getParticipantCookie() {
    const esToken = asa.getCookie("token");
    const nick = asa.getCookie("nick");
    const userKey = asa.getCookie("userKey");
    const testKey = asa.getCookie("testKey");
    const testID = asa.getCookie("testID");

    return {esToken, nick, userKey, testKey, testID}
}


function userValidate() {

    const userCookies = getParticipantCookie();

        
    return asa.ajax({
        url:urlRoot+"/src/database/userService/userConn.php",
        data:{
            checkToken: 1
        }
    })
    .then(respData=>{
        const resp = JSON.parse(respData)
        if (resp.access == "ok"){
            return true;
        }
        return false;
        // if (resp.error == "noConn" || resp.error == "noAnswer"){
        //     resolve(false);
        // }
    })

}