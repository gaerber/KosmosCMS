function tableRowEffectHover()
{
    // is the element already selected?
    if (this.className.indexOf("tre-select") == -1) {
        // no? then show the hover effect
        this.className += " tre-hover";
    }
}

function tableRowEffectUnhover()
{
    // remove the hover effect
    this.className = this.className.replace(/\b(tre-hover)\b/, "");
}

function tableRowEffectSelect()
{
    // is the element already selected?
    if (this.className.indexOf("tre-select") != -1) {
        // then remove the selection (toggle click)
        this.className = this.className.replace(/\b(tre-select)\b/, "");
    } else {
        // othervise show the selection effect
        // remove the hover effect
        this.className = this.className.replace(/\b(tre-hover)\b/, "");
        this.className += " tre-select";
    }
}

initTableRowEffect = function()
{
    var tableObjects = document.getElementsByTagName('TABLE');
    for (var t = 0; t < tableObjects.length; t++) {
        //if (tableObjects[t].className.indexOf("table_hide") != -1) {
            var tableRowObjects = tableObjects[t].getElementsByTagName('TR');
            for (var tr = 0; tr < tableRowObjects.length; tr++) {
                if (tableRowObjects[tr].className.indexOf("table_title") == -1) {
                    tableRowObjects[tr].onmouseover = tableRowEffectHover;
                    tableRowObjects[tr].onmouseout  = tableRowEffectUnhover;
                    //tableRowObjects[tr].onclick     = tableRowEffectSelect;
                }
            }
        //}
    }
}

window.onload = initTableRowEffect;