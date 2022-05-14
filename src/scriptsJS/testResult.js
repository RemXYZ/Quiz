class ResultManipulation {
    sendResult(option={"noAsk":false,"immediately":false}){
        const qNumber = asa.getCookie("questionNum") ?? 0;
        const qMax = asa.getCookie("qMax") ?? 15;
        if ((Number(qNumber) >= Number(qMax)-1) || option.immediately == true) {
            if (option.noAsk == false) {
                let continueSubmit = confirm("Czy na pewno chcesz wysłać wszystkie odpowiedzi ?");
                if (!continueSubmit) {
                    location.reload();
                    return false;
                }
            }
            return asa.ajax({
                url:urlRoot+"/src/database/queryTest/sendResult.php",
                data:{}
            })
            .then(resp=>{
                const outData = JSON.parse(resp);
                if (outData.responde == "waitForOthers") {
                    location.reload();
                    //alert("Poczekaj na pozostałych :)");
                }
                if (outData){
                    this.resultData = outData;
                    return true;
                }
                if (outData.error == "noRepeat"){
                    return false;
                }
                return false;
            })
        }
        
    }


    getResult(){
        return asa.ajax({
            url:urlRoot+"/src/database/queryTest/sendResult.php",
            data:{checkResult:true}
        })
        .then(resp=>{
            const outData = JSON.parse(resp);
            if (outData != false) {
                this.resultData = outData;
                return true;
            }
            return false;
        })
    }


    displayResult(){
        const resultData = this.resultData;

        // display points
        const fullPointsDiv = $(".fullPoints");
        let fullPoints = Number(resultData.full_points);
        let pointsMsg = ["Masz ", " punkt", " punkty", " punktów"];
        let points;
        if (fullPoints % 10 == 1) {
            points = pointsMsg[1];
        }
        else if (fullPoints % 10 < 5){
            points = pointsMsg[2];
        }
        else if (fullPoints % 10 >= 5){
            points = pointsMsg[3];
        }
        fullPointsDiv.html(pointsMsg[0]+fullPoints+points);

        // logout
        const logoutDiv = $('.logoutBtn');
        logoutDiv.html("Wyloguj się");
        logoutDiv.on("click", ()=>{
            let continueLogout = confirm("Czy na pewno chcesz wologować się ?");
            if (continueLogout) {
                exitFromQuiz();
            }
        })

    }
}