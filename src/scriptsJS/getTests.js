class QuestionManipulation {

    constructor() {
        this.questionData;
        this.answersDiv;
        this.qMax;
        this.qNum;
        this.warning = false;
    }


    // #zadaniem tej funkcji jest polecenie, ze jestem gotow, aby odebrac pytanie
    requestQuestion() {
        const participantCurrentQuestion = asa.getCookie("question");
        return asa.ajax({
            url:urlRoot+"/src/database/queryTest/queryTest.php"
        })
        .then(resp=>{
            
            const outData = JSON.parse(resp);
            if (outData.error == "waitingMode") return false;
            this.questionData = outData;
            return outData;
        })
    }


    getQuestion() {
        return this.questionData;
    }


      /////////////////////////////////////
     ////////SELECTING QUESTIONS//////////
    /////////////////////////////////////


    selectAnswer(el, optionsArg = {}) {
        let options = {
            skipStorage:false,
            ...optionsArg
        }
        let elStatus = el.answerNumStorageGet();
        const elCircle = el.children(".answerCircle");
        let myAnswersData = localStorage.getItem("myAnswer");
        if (!elStatus?.pressed) {
            // #if answer has choised
            // #and button has pressed
            if (!options.skipStorage) {
                if (myAnswersData && myAnswersData != "null") {
                    let myAnswers = JSON.parse(myAnswersData);
                    myAnswers.push(elStatus.num);
                    let myAnswersOut = JSON.stringify(myAnswers);
                    localStorage.setItem("myAnswer", myAnswersOut)
                }else {
                    //first pressing
                    let myAnswerNum = JSON.stringify([elStatus.num])
                    localStorage.setItem("myAnswer", myAnswerNum)
                }
            }
            
            el.answerNumStorageSet({"pressed":true});
            elCircle.addClass("answerCircleActive");
        }else {
            // #if we removed this answer
            let myAnswers = JSON.parse(myAnswersData);
            let myAnswersArr = myAnswers.filter((ans)=>{
                return ans != elStatus.num
            })
            
            if (myAnswersArr.length != 0) {
                let myAnswersOut = JSON.stringify(myAnswersArr);
                localStorage.setItem("myAnswer", myAnswersOut)
            }else {
                localStorage.removeItem("myAnswer")
            }

            el.answerNumStorageSet({"pressed":false});
            elCircle.removeClass("answerCircleActive");
        }
    }

    // ADDED 26.02.2022
    selectAnswerAuto(pos){
        const myAnswersData = localStorage.getItem("myAnswer");
        const answersDiv = this.answersDiv;
        
        if (myAnswersData && myAnswersData != "null") {
            //console.log(myAnswersData)
            let myAnswers = JSON.parse(myAnswersData);
            for (let i = 0; i < myAnswers.length;i++){
                let selectedDiv = answersDiv[myAnswers[i]];
                this.selectAnswer(selectedDiv,{skipStorage: true})
            }
            
        }

        
    }

    // ADDED 25.02.2022
    getAnswersFromHistory(qNumber) {
        const aHis = localStorage.getItem("answersPositionHistory")
        if (aHis && aHis != null && aHis != "null") {
            const answers = JSON.parse(aHis)
            for (let [k,v] of Object.entries(answers)) {
                if (qNumber == v.qNum) {
                    return {"answer":v, "answersPositionIndex":k};
                } 
            }
        }
        return null;
    }

    // ADDED 25.02.2022
    pushToAnswersHistory(qNum,answersPos,answer) {
        const answerFromHis = this.getAnswersFromHistory(qNum);
        const aHis = localStorage.getItem("answersPositionHistory");
        const answerData = {
            qNum,
            answersPos,
            selectedAnswer: answer
        }
        //console.log(answerData)
        if (!aHis) {
            localStorage.setItem("answersPositionHistory", JSON.stringify([answerData]));
        }else {
            
            let answersInfo;
            
            if (answerFromHis !== null) {
                answersInfo = JSON.parse(aHis);
                let aIx = answerFromHis.answersPositionIndex;
                answersInfo[aIx] = answerData;
            }else {
                answersInfo = JSON.parse(aHis);
                answersInfo.push(answerData);
            }
            localStorage.setItem("answersPositionHistory", JSON.stringify(answersInfo));
        }
    }

    // ADDED 25.02.2022
    showSelectedAnswer(){
        const qNumber = asa.getCookie("questionNum") ?? 0;
        const answerFromHis = this.getAnswersFromHistory(qNumber);
        if (answerFromHis !== null) {
            this.questionData.answers = answerFromHis.answer.answersPos;

            let myAnswersOut = JSON.stringify(answerFromHis.answer.selectedAnswer);
            localStorage.setItem("myAnswer", myAnswersOut);
            return true;
            // this.answersDiv
            // this.questionData
        }
        return false;
    }


      /////////////////////////////////////
     /////////QUESTION SELECTOR///////////
    /////////////////////////////////////


    switchQuestion(el){
        let qNum = this.qNum;
        const cookieTime = 2100;
        let selectNum = el.attr("qNum");
        // !Setting cookie, chang when public
        asa.setCookie("questionNum", selectNum, {"max-age":cookieTime});

    }


    launchQuestionSelector(callback) {
        let questionSelector = $(".questionSelector");
        const maxNumberData = this.qMax;
        
        let colNum = 2;
        let rowNum = Math.ceil(maxNumberData / colNum);
        let qNum = 1;
        for (let row = 0; row < rowNum; row++) {
            //crEl to jest moja wlasna funkcja
            let qOptionRowClass = ".questionOptionRow";
            if (row < 1) qOptionRowClass = "";

            questionSelector.crEl("tr", qOptionRowClass, (el)=>{
                for(let j = 0; j < colNum; j++) {
                    if (qNum <= maxNumberData){
                        let qOptionClass = ".questionOption";
                        let qNumStr = qNum;
                        let qNumOut = qNum-1;
                        if (qNum <= 2){
                            qOptionClass = ".firstQuestionOption";
                        }
                        if (qNum < 10) qNumStr = "0"+qNum;
                        let qOption = el.crEl("td",qOptionClass).html(qNumStr);
                        qOption.attr("qNum", qNumOut);
                        qOption.addClass("q"+qNumOut.toString());

                        qOption.on("click", ()=> {
                            this.switchQuestion(qOption);
                            callback(qOption);
                        })
                        qNum++
                    }
                } 
            });
        }
    }

    
    displaySelectedQuestion() {
        let qNum = this.qNum;
        qNum.toString();
        let qOptionClass = ".q"+qNum;
        let qOption = $(qOptionClass)
        highlightQuestionOption(qOption)
    }


      /////////////////////////////////////
     ///////DISPLAING QUESTIONS//////////
    ////////////////////////////////////


    displayQuestion() {
        const questionData = this.questionData;
        if (questionData.overTime == true) {
            return {"warning":"overTime"};
        }

        // #header
        const qHeader = questionData.q_header;
        const testsH = $(".testsH");
        testsH.html(qHeader);

        // timer
        let testsTimer = $(".tests_timer");
        if (questionData.session_mode === null) {
            questionData.unix_time_now = asa.getMyTime().unix;

            let dateStr = questionData.log_date.replace(/ /,"T");
            let logDate = asa.toUnix({str:dateStr})
            questionData.unix_session_time = logDate;
            
            questionData.unix_time_now = 0
            questionData.unix_session_time = 0
        }
        let test_time = Number(questionData.unix_time_now) - Number(questionData.unix_session_time) - Number(questionData.test_time) - Number(questionData.waiting_time);

        if (test_time > 0) {
            sendResultImmidiatly();
            return {"warning":"overTime"};
        }
        
        if (questionData?.error != "noAnswer") {
            turnOffTimer();
            let newTimerIndex = +new Date;
            let timer = turnOnTimer(testsTimer, Math.abs(test_time), newTimerIndex);
            switchTimer(newTimerIndex);
            
            timer.then(resp=>{
                if (resp == true) {
                    sendResultImmidiatly();
                    return {"warning":"overTime"}; 
                }
            })
        }

        // #number
        // #max number of questions in quiz
        const maxNumber = asa.getCookie("qMax") ?? 15;
        this.qMax = maxNumber;
        const qNumber = asa.getCookie("questionNum") ?? 0;
        this.qNum = qNumber;
        let qNumberStr = Number(qNumber)+1;
        if (qNumberStr < 10) {
            qNumberStr = "0"+qNumberStr;
        }
        const qNumberDiv = $(".currentQuestion");
        qNumberDiv.html(`${qNumberStr}/${maxNumber}`)


        // #answers
        let answerClass = "answer";
        let answerClassOdd = "answerL";
        let answerClassEven = "answerR";
        let answerClassCircle = "answerCircle";

        let answerBG = [
            "rgba(231, 86, 54, 0.8)", 
            //"rgba(112, 226, 42, 0.8)",
            "rgba(105, 214, 38, 0.8)",
            "rgba(140, 81, 236, 0.8)",
            "rgba(81, 171, 236, 0.8)"
        ]
        const answersBox = $(".answersBox");
        answersBox.children().each(function(){
            let myClass = this.classList[0];
            if (myClass == answerClass) {
                this.remove();
            }
        })

        const answers = questionData.answers;
        this.answersDiv = [];
        if (questionData?.error == "noAnswer") {
            return {"warning":"noData"};
        }
        
        const answersNum = answers.length;


        for (let i = 1; answersNum >= i; i++) {
            let answerDiv = answersBox.crEl("div","."+answerClass, el=>{
                // #push number of question
                let elCirlce;
                if (i % 2 == 0){ 
                    elCirlce = el.crEl("span", "."+answerClassCircle).html(i)
                    el.addClass(answerClassEven)
                }else {
                    el.addClass(answerClassOdd)
                }

                // #work with answer itself
                el.append(answers[i-1])
                // #jQuery addition
                el.answerNumStorage = {}
                el.answerNumStorageSet = function (options) {
                    const settings = $.extend({
                        // #These are the defaults.
                        num: options?.num ?? this.answerNumStorageGet().num,
                        pressed: options.pressed ?? this.answerNumStorageGet().pressed ?? false
                    }, options );

                    this.answerNumStorage["pressed"] = settings.pressed;
                    this.answerNumStorage["num"] = settings.num;
                    return true;
                }
                el.answerNumStorageGet = function () {
                    return this.answerNumStorage;
                }
                el.answerNumStorageSet({"num":i-1});
                    

                if (answersNum == 2) {
                    if (i == 1) {
                        el.css({"background":answerBG[0]})
                    }else if (i == 2) {
                        el.css({"background":answerBG[3]})
                    }
                    
                }else {
                    el.css({"background":answerBG[i-1]})
                }
                
                if (i % 2 != 0) {
                    elCirlce = el.crEl("span", "."+answerClassCircle).html(i)
                }

                el.on("click", ()=>{
                    this.selectAnswer(el)
                })

                
                this.answersDiv.push(el)

            })//end of callback
        }
    }


    displayImg() {
        const qData = this.questionData;
        //console.log(qData)
        const imgSrc = urlRoot+"/src/imgs";

        const bgDiv = $(".root");
        const frontDiv = $(".testsImage");

        bgDiv.css({
            "background-image":"none",
            "box-shadow":"none"
        });
        frontDiv.css("opacity","0");

        const bgImg = qData?.bg_img ?? null;
        const frontImg = qData?.front_img ?? null;

        if (bgImg !== null) {
            let author = qData?.bg_img_author;
            let link = qData?.bg_author_link;
            let source = qData?.bg_img_src;
            if (source === undefined) return false;
            bgDiv.css({
                "background-image": "url("+imgSrc+source+")",
                "box-shadow": "inset 0 0px 10px 1000px rgba(0, 0, 0, 0.2)"
            })
        }
        if (frontImg !== null) {
            
            let author = qData?.front_img_author;
            let link = qData?.front_author_link;
            let source = qData?.front_img_src;
            if (source === undefined) return false;
            frontDiv.attr("src",imgSrc+source);
            frontDiv.css("opacity","1");
            // setTimeout(()=>{
            //     frontDiv.css("opacity","1");
            // }, 0);
            
        }
    }

      /////////////////////////////////////
     ////////SENIDNG QUESTIONS////////////
    ////////////////////////////////////

    sendAnswer() {
        let myAnswersData = localStorage.getItem("myAnswer");
        let myAnswers = JSON.parse(myAnswersData);
        let myAnswersOut = [];
        // let onUpdate = 0;
        // let questionID = asa.getCookie("currentQuestion");
        let answersData = JSON.parse(asa.getCookie("currentAnswers"));
        let answers = this.questionData.answers;

        const qNumber = asa.getCookie("questionNum") ?? 0;
        const answerFromHis = this.getAnswersFromHistory(qNumber);

        //checks for matches between cookies and local information
        //If there are some problem, it means, that someone have change cookie !!!
        if (myAnswers) {
            if (answerFromHis === null) {
                for (let i = 0; i < answers.length;i++){
                    if (answers[i] != answersData[i]) {
                        location.reload();
                    }
                }
                myAnswersOut = myAnswers;
            }else {
                // here i write answers to myAnswersOut
                for (let i = 0; i < myAnswers.length;i++){
                    let writeAnswer = answers[myAnswers[i]];
                    myAnswersOut.push(writeAnswer);
                }
            }  
        }
        if (myAnswers === null || myAnswers.length == 0) 
            myAnswersOut = [null];

        this.pushToAnswersHistory(qNumber, answers, myAnswers);        
        
        return asa.ajax({
            url:urlRoot+"/src/database/queryTest/sendTest.php",
            data:{
                myAnswers:JSON.stringify(myAnswersOut)
            }
        })
        .then(resp=>{
            // console.log(resp)
            const outData = JSON.parse(resp);
            if (outData.answer == "save") {
                return true;
            }
            if (outData.answer == "update") {
                return true;
            }
            if (outData.error == "overMax") {
                return false;
            }
            return false;
        })
    }


    overMaxBtn(el) {
        const qNumber = asa.getCookie("questionNum") ?? 0;
        const qMax = asa.getCookie("qMax") ?? 15;
        if (Number(qNumber) >= Number(qMax)-1) {
            el.css({
                "background": "#494949",
            })
            el.html("Wyślij");
        }else {
            el.css({
                "background": "#828282",
            })
            el.html("Dalej");
        }
        return;
    }


}//end of QuestionManipulation


function genQuestions() {
    return asa.ajax({
        url:urlRoot+"/src/database/queryTest/queryTest.php",
        data:{"genQuestion":1}
    })
    .then(resp=>{
        // let d = document.createElement("div")
        // d.innerHTML = resp
        // document.body.append(d);
        // console.log(resp)
        const outData = JSON.parse(resp);
        return outData;
    })
    // .catch(e=>{
    //     console.error(e)
    //     console.error("bad answer, use JSON");
    //     return false;
    // })
}
