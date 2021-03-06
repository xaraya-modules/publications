// Javascript from tutorial found on:
// URL: http://clagnut.com/sandbox/imagefades/
// Discussion found on subject:
// URL: http://clagnut.com/blog/1299/

document.write("<style type='text/css'>#thephoto {visibility:hidden;}</style>");

function publications_pictures_initImage() {
    imageId = 'thephoto';
    image = document.getElementById(imageId);
    publications_pictures_setOpacity(image, 0);
    image.style.visibility = "visible";
    publications_pictures_fadeIn(imageId,0);
}
function publications_pictures_fadeIn(objId,opacity) {
    if (document.getElementById) {
        obj = document.getElementById(objId);
        if (opacity <= 100) {
            publications_pictures_setOpacity(obj, opacity);
            opacity += 10;
            window.setTimeout("publications_pictures_fadeIn('"+objId+"',"+opacity+")", 100);
        }
    }
}
function publications_pictures_setOpacity(obj, opacity) {
    opacity = (opacity == 100)?99.999:opacity;
    // IE/Win
    obj.style.filter = "alpha(opacity:"+opacity+")";
    // Safari<1.2, Konqueror
    obj.style.KHTMLOpacity = opacity/100;
    // Older Mozilla and Firefox
    obj.style.MozOpacity = opacity/100;
    // Safari 1.2, newer Firefox and Mozilla, CSS3
    obj.style.opacity = opacity/100;
}
window.onload = function() {publications_pictures_initImage()}
