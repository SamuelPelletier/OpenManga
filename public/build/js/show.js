/**
 * Created by Samuel on 17/11/2017.
 */

var imageList = getImageList()
var flagLoad = false;
/*
$( window ).on( "load", function() {
    var historyTraversal = event.persisted || ( typeof window.performance != "undefined" && window.performance.navigation.type === 2 );
  if ( historyTraversal ) {
    // Handle page restore.
    window.location.reload();
  }
    setTimeout(function(){
        if(flagLoad == false){
            $("body").append('<div class="loader"><div class="loader-inner"><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div></div></div>');
        }
    }, 100); //wait 20 ms
});*/


$('.img-view').on('click',function(){
    viewer($(this).attr('src'))
})

function viewer(path) {
    toggleFullscreen()
    var intervalID = undefined;
    var name = path.split("/").pop();
    var onlyPath = path.substring(0, path.length - name.length)
    $("body").css("overflow", "hidden")
    $("body").append("<div class='viewer' style='display: none'><div class='fullscreen'></div><div class='cross'></div><div class='container-img'><img  class='imgViewer' src=\"" + path + "\"><div class='control'><div class='full-back'></div><div class='backImage'></div><div class='start'></div><div class='pause'></div><div class='nextImage'></div><div class='full-next'></div></div></div></div>")
    $(".viewer").fadeIn("slow")

    $(".pause").hide()

    $(".cross").click(function () {
        $(".pause").hide()
        $(".start").show()
        clearInterval(intervalID);
        intervalID = undefined;
        $(".viewer").fadeOut(function () {
            $(".viewer").remove()
            $("body").css("overflow", "visible")
        })
    })

    $(".fullscreen").click(function () {
        var docElm = document.documentElement;
        if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
        }
        else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
        }
        else if (docElm.webkitRequestFullScreen) {
            docElm.webkitRequestFullScreen();
        }
    })

    $(".full-back").click(function () {
        var firstName = imageList[0]
        $(".imgViewer").attr("src", onlyPath.concat(firstName))
        name = firstName
    })

    $(".backImage").click(function () {
        var predName = imageList[getPred(name)]
        $(".imgViewer").attr("src", onlyPath.concat(predName))
        name = predName
    })

    $(".start").click(function () {
        if (intervalID == undefined) {
            $(".start").hide();
            $(".pause").show()
            intervalID = setInterval(function () {
                var predName = imageList[getNext(name)]
                $(".imgViewer").fadeTo(1000,0.1);
                setTimeout(function () {
                    $(".imgViewer").attr("src", onlyPath.concat(predName)).fadeTo(1000,1)
                }, 1000);
                name = predName
            },  $(".input-time").val()*1000);
        }
    })

    $(".start").hover(function () {
        if($(".time").length == 0){
            $(".start").after("<div class='time'></div>")
            $(".time").hide()
            $(".time").append("<input class='input-time' min='2' max='99' value='5'></input>")
            $(".time").append("<label class='time-second'>s</label>")
        }
        $(".time").fadeIn("slow")

        $(".time").hover(function () {
            $(".time-second").remove()
            $(".input-time").prop('type', 'number');
            $(".input-time").css('text-align','right')
            $(".input-time").css('width','36px')
        }, function(){
            if($(".time-second").length == 0){
                $(".time").append("<label class='time-second'>s</label>")
            }
            if($(".input-time").val() == ''){
                $(".input-time").val(5)
            }
            $(".input-time").css('width','28px')
            $(".input-time").css('text-align','center')
            $(".input-time").prop('type', 'text');
            $(".input-time").css('text-align','center')
            $(".time").fadeOut("slow")
        })
        
    })

    

    $(".pause").click(function () {
        $(".pause").hide()
        $(".start").show()
        clearInterval(intervalID);
        intervalID = undefined;
    })

    $(".nextImage").click(function () {
        var nextName = imageList[getNext(name)]
        $(".imgViewer").attr("src", onlyPath.concat(nextName))
        name = nextName
    })

    $(".full-next").click(function () {
        var lastName = imageList[imageList.length - 1]
        $(".imgViewer").attr("src", onlyPath.concat(lastName))
        name = lastName
    })

    var ready = true;

    $("img").mouseover(function (e) {
        if (ready == true) {
            ready = false
            $(".control").fadeIn("slow", function () {
                ready = true
            })
        }
    })

    $("img").mousemove(function (e) {
        if (ready == true) {
            ready = false
            $(".control").fadeIn("slow", function () {
                ready = true
            })
        }
    })

    $("img").mouseout(function (e) {
        if (e.relatedTarget && !["control", "start", "pause", "back", "next", "full-back", "full-next","time","input-time","time-second"].includes(e.relatedTarget.className)) {
            if (ready == true) {
                ready = false
                $(".control").fadeOut("slow", function () {
                    ready = true
                })
            }
        }
    })

    $(document).keydown(function (e) {
        switch (e.keyCode) {
            case 39:
                var nextName = imageList[getNext(name)]
                $(".imgViewer").attr("src", onlyPath.concat(nextName))
                name = nextName
                break
            case 37:
                var predName = imageList[getPred(name)]
                $(".imgViewer").attr("src", onlyPath.concat(predName))
                name = predName
                break
            case 32:
                if (intervalID == undefined) {
                    $(".start").hide();
                    $(".pause").show()
                    intervalID = setInterval(function () {
                        var predName = imageList[getNext(name)]
                        $(".imgViewer").attr("src", onlyPath.concat(predName))
                        name = predName
                    }, 3500);
                } else {
                    $(".pause").hide()
                    $(".start").show()
                    clearInterval(intervalID); // useless ?
                    intervalID = undefined
                }
                break
        }
    });
}

function getImageList() {
    imageList = []
    $.each($('img'),function(index,value){
        imageList.push($(value).attr('src').split('/').pop())
    })
    return imageList
}

function getPred($imgName) {
    var index = imageList.indexOf($imgName)
    if (index == 0) {
        return imageList.length - 1
    }
    return index - 1;
}

function getNext($imgName) {
    var index = imageList.indexOf($imgName)
    if (index == imageList.length - 1) {
        return 0
    }
    return index + 1;
}

function toggleFullscreen() {
    document.addEventListener("fullscreenchange", function () {
        if (document.fullscreen) {
            $(".fullscreen").hide()
        } else {
            $(".fullscreen").show()
        }
    }, false);

    document.addEventListener("mozfullscreenchange", function () {
        if (document.mozFullScreen) {
            $(".fullscreen").hide()
        } else {
            $(".fullscreen").show()
        }
    }, false);

    document.addEventListener("webkitfullscreenchange", function () {
        if (document.webkitIsFullScreen) {
            $(".fullscreen").hide()
        } else {
            $(".fullscreen").show()
        }
    }, false);
}
