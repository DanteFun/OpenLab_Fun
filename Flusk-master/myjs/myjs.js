$(document).ready(function(){
    $("#Login_Button").click(function(){
        document.getElementById('Login_name').value='';
        document.getElementById('Login_password').value='';
        if($("#Login").css("display") == "none")
        {
            $("#Login").fadeIn("slow");
        }
        else
        {
            $("#Login").hide();
        }
    });
});
$(document).ready(function(){
    $("#Register_Button").click(function(){
        if($("#Register").css("display") == "none")
        {
            $("#Register").fadeIn("slow");
        }
        else
        {
            $("#Register").hide();
        }
    });
});




