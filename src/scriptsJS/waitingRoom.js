class WaitingRoom {
    constructor() {
        this.stopAnimation = false;
    }
    createSession() {
        return asa.ajax({
            url:urlRoot+"/src/database/userService/waitingRoom.php",
            data:{"createSession":1}
        })
        .then(resp=>{
            const outData = JSON.parse(resp);
            if (outData == true) {
                return true;
            }
            return false;
        })
    }


    getSessionState() {
        return asa.ajax({
            url:urlRoot+"/src/database/userService/waitingRoom.php",
            data:{"sessionState":1}
        })
        .then(resp=>{
            const outData = JSON.parse(resp);
            this.respData = outData;
            return false;
        })
    }


    runWRoomTimer(el, time) {
        let min = Math.floor(time/60);
        let sec = Math.round((time/60 - min) * 60)
        if (sec < 10) sec = "0"+sec;
        el.html(min+":"+sec);
    }


    displayWaitingRoom(callback) {
        const wRoomData = this.respData;
        if (!wRoomData) {
            // #Please change these lines later !
            sendResultImmidiatly();
            asa.rmCookie("waitingMode");
            asa.rmCookie("waitForOthers");
            location.reload();
            return false;
        }
        //console.log(wRoomData);
        // #SESSION ADDITION
        let waitForOthers = asa.getCookie("waitForOthers") ?? 0;

        const startBtn = $(".waitingRoomStartBtn");
        const sBtnCir1 = $(".wRSB_Circle");
        const sBtnCir2 = $(".wRSB_Circle2");

        const waitingRoomTimer = $(".waitingRoomTimer");

        const currentMemberNum = $('.currentMemberNum');
        const maxMemberNum = $('.maxMemberNum');
        currentMemberNum.html(wRoomData.session_participant_num)
        maxMemberNum.html("/"+wRoomData.participants_max)

        let wRoomTimer = Number(wRoomData.unix_time_now) - Number(wRoomData.unix_session_time) - Number(wRoomData.waiting_time);
        if (waitForOthers == 1) {
            wRoomTimer -= Number(wRoomData.test_time);
            this.runWRoomTimer(waitingRoomTimer, Math.abs(wRoomTimer));
        }

        if (wRoomTimer < 0 && waitForOthers == 0) {
            this.runWRoomTimer(waitingRoomTimer, Math.abs(wRoomTimer));
        }
        else if (this.stopAnimation == false) {
            this.stopAnimation = true;
            this.runWRoomTimer(waitingRoomTimer, 0);
            sBtnCir1.addClass("wRSB_Circle_active");
            sBtnCir2.addClass("wRSB_Circle2_active");
            //this.startQuiz()
            sBtnCir1.on("click", callback);
        }
        //turnOnWRoomTimer(waitingRoomTimer, wRoomTimer);
        
    }


    startQuiz() {
        return asa.ajax({
            url:urlRoot+"/src/database/userService/waitingRoom.php",
            data:{"startQuiz":1}
        })
        .then(resp=>{
            return true;
        })
    }


}

function checkForWaitingMode() {
    return asa.ajax({
        url:urlRoot+"/src/database/userService/waitingRoom.php",
        data:{"checkWaitingMode":1}
    })
    .then(resp=>{
        const outData = JSON.parse(resp);
        if (outData == true) {
            return true;
        }
        return false;
    })
}