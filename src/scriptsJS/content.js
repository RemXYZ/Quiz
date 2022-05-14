const mainHall = $(".mainHall");

const waitingRoom = $(".waitingRoom");

const root = $(".root");

const resultRoom = $(".resultRoom");

function showMainHall() {
    root.hide();
    mainHall.show();
}


function showWaitnigRoom(immediately = true){
    if (immediately) {
        mainHall.remove();
    }
    waitingRoom.css({"display":"grid"});
}


function showQuiz(immediately = true) {
    if (immediately) {
        mainHall.remove();
        waitingRoom.remove();
    }
    root.show();
    return true;
}


function showResult() {
    root.remove();
    resultRoom.show();
    return true;
}


function showScrollAnimation(whichOn) {
    return new Promise((resolve, reject)=>{
        // let scrollPos = 0;
        let windowTop = window.innerHeight;
        let el;
        let showFunction;
        if (whichOn == "quiz") {
            el = root;
            showFunction = showQuiz;
        }
        else if (whichOn == "wRoom") {
            el = waitingRoom;
            showFunction = showWaitnigRoom;
        }
        showFunction(false)

        let rootTop = el.position().top;

        let scrollAnimationVar;
    

        function scrollAnimation() {
            let docTop = $(document).scrollTop();
            let animationSpeed = 35;
            if (docTop < windowTop/10) {
                animationSpeed = 10;
            }
            if (docTop < windowTop/3) {
                animationSpeed = 25;
            }

            if (docTop + windowTop/5 > rootTop) {
                animationSpeed = 20;
            }

            // scrollPos += animationSpeed;
            window.scrollBy(0, animationSpeed);
        
            scrollAnimationVar = requestAnimationFrame(scrollAnimation);

            //if window is scrolled, we can stop animation and start quiz
            if (docTop+1 >= rootTop) {
                // mainHall.remove();
                showFunction()
                cancelAnimationFrame(scrollAnimationVar);
                resolve(true);
            }
        }

        scrollAnimationVar = requestAnimationFrame(scrollAnimation);

    })
}


function turnOnTimer(el, time = 0, key = null) {
    if (el == 0) return true;
    let myLastTime;
    let maxMin = time; // 30 min is 1800
    let min = Math.floor(maxMin / 60);
    let sec = Math.round((time/60 - min) * 60)
    let hours = 0;
    if (myLastTime = localStorage.getItem('myTimer')) {
        myLastTime = myLastTime.split(":");
        min = parseInt(myLastTime[0]);
        sec = parseInt(myLastTime[1]);
    }

    localStorage.setItem("stopTimer", 0);
    
    return new Promise(resolve=>{
        let quizTimer = setInterval(() => {
            if (localStorage.getItem("newTimer") != key || localStorage.getItem('stopTimer') == 1) {
                clearInterval(quizTimer);
                localStorage.removeItem('myTimer');
                return;
                
            }
            if (min <= 0 && sec <= 0) {
                el.html("00"+":"+"00")
                turnOffTimer();
                clearInterval(quizTimer);
                resolve(true);
                return true;
            }
            
            if (sec <= 0) {
                sec = 60
                min--
            }
            sec--;

            let outMaxMin = min;
            let outSec = sec;
            if (min < 10 ) {
                outMaxMin = "0"+min;
            }
            if (sec < 10 ) {
                outSec = "0"+sec;
            }
            el.html(outMaxMin+":"+outSec);
            localStorage.setItem("myTimer", outMaxMin+":"+outSec);
            
        }, 1000);
    })
}


function turnOffTimer() {
    localStorage.removeItem('myTimer');
    localStorage.setItem("stopTimer", 1);
}


function switchTimer(index) {
    localStorage.setItem("newTimer", index);
}


function turnOnWRoomTimer(el, time) {
    turnOnTimer(el, time);
}


function displayQuestionSelector() {
    const selectorArrow = $(".openQuestionSelector");
    selectorArrow.on("click", function(){
        let el = $(this);
        let questionOption = $(".questionOptionRow");

        let classDown = "openQuestionSelectorDown";
        let classUp = "openQuestionSelectorUp";
        if (el.hasClass(classDown)){
            // roll out
            el.removeClass(classDown);
            el.addClass(classUp);
            questionOption.css({"display":"table-row"});
        }else {
            // roll up
            el.removeClass(classUp);
            el.addClass(classDown);
            questionOption.css({"display":"none"});
        }
    })
}


function highlightQuestionOption(el){
    let otherSelected = $(".activeSelectedQuestion");
    otherSelected.each((i, el)=>{
        $(el).removeClass("activeSelectedQuestion");
    })
    el.addClass("activeSelectedQuestion");
}


function exitFromQuiz() {
    console.log("hergegh")
    return asa.ajax({
        url: urlRoot+"/src/database/userService/logout.php",
        data: {"user":"participant"}
    })
    .then(resp=>{
        console.log("hh")
        if (resp == "ok") {
            location.reload();
        };
        return false;
    })
}


function authorDataManipulation(author, link) {
    if(author === null) {
        author = "Nieznany"
    }
    if(link === null) {
        link = "Internet"
    }
    return [author,link];
}


function launchAuthorReview(quizData) {
    const authorReviewBtn = $(".authorReviewBtn");

    const authorReview = $(".authorReview");
    authorReview.css("display","block")

    const authorReviewList = $(".authorReviewList");
    authorReviewList.html("");

    const qData = quizData.questionData;
    const bgImg = qData?.bg_img ?? null;
    const frontImg = qData?.front_img ?? null;

    if (qData?.user_nickname) {
        authorReviewList.crEl("li","questionAuthor").html("Autor pytania: "+ qData.user_nickname)
    }
    


    if (bgImg !== null) {
        let authorIn = qData?.bg_img_author;
        let linkIn = qData?.bg_author_link;
        [author,link] = authorDataManipulation(authorIn,linkIn);
        authorReviewList.crEl("li",".frontAuthorRow",el=>{
            el.crEl("span",".frontAuthorName").html("Autor zdjęcia w tle: "+author+"<br> Źródło: ")
            if (linkIn === null)
                el.crEl("sapn",".frontAuthorLink").html(link)
            else
                el.crEl("a",{"class":".frontAuthorLink","href":linkIn}).html("link")
        })
        
    }
    if (frontImg !== null) {
        let authorIn = qData?.front_img_author;
        let linkIn = qData?.front_author_link;
        [author,link] = authorDataManipulation(authorIn,linkIn);
        console.log(author)
        authorReviewList.crEl("li",".frontAuthorRow",el=>{
            el.crEl("span",".frontAuthorName").html("Autor zdjęcia przedniego: "+author+"<br> Źródło: ")
            if (linkIn === null)
                el.crEl("sapn",".frontAuthorLink").html(link)
            else
                el.crEl("a",{"class":".frontAuthorLink","href":linkIn}).html("link")
        })
    }
    
    /// POSITION ///
    let btnCord = authorReviewBtn.offset()
    let reviwBoxSize = [authorReview.innerHeight(), authorReview.innerWidth()]
    authorReview.css({
        "top": btnCord.top-reviwBoxSize[0]+"px", 
        "left": btnCord.left-reviwBoxSize[1]/2+"px"
    })
}