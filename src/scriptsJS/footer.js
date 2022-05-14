const footer = $(".quizFooter");

function launchFooter() {
    //adjust footer height////
    let wHt = window.innerHeight;
    let fHt = footer.position().top;
    if (wHt >= fHt)
        footer.css({height: wHt - fHt + "px"});

    
    //show autors release
    
}
