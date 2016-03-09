/**
 * Yippee-ki-yay, motherfucker!
 * It's a common js
 */




/**
 * Запись одной строки в лог
 * @param message Не поверите, та самая строка
 */
function logLine(message){
    if (message !== '') {
        var d = new Date();
        var t = (d.getHours() > 9 ? d.getHours() : '0' + d.getHours()) + ':' +
            (d.getMinutes() > 9 ? d.getMinutes() : '0' + d.getMinutes()) + ':' +
            (d.getSeconds() > 9 ? d.getSeconds() : '0' + d.getSeconds());
        $("#logPre").text($("#logPre").text() + '[' + t + '] ' + message + "\n");
    }else{
        $("#logPre").text($("#logPre").text() + "\n");
    }
}



/**
 * Выполнение одного этапа задачи с логгированием результатов
 * @param act Действие для передачи в контроллер
 */
function nextStep(act){
    $.ajax({
        type: 'GET',
        url: '/controller.php?action=' + act,
        dataType: 'json',
        timeout: 240000,
        success: function (data) {
            if (data.success) {
                logLine(data.message == ''
                    ? "Завершено"
                    : "Завершено. " + data.message
                );
                if (typeof data.nextStep !== 'undefined'){
                    logLine('');
                    logLine(data.nextMessage);
                    nextStep(data.nextStep);
                }else{
                    $("#beginBtn").show();
                    $("#logLoader").hide();
                }
            }else{
                logLine("# Произошла ошибка: " + data.message);
                $("#beginBtn").show();
                $("#logLoader").hide();
            }
        },
        error: function() {
            logLine("# Произошла ошибка");
            $("#logLoader").hide();
            $("#beginBtn").show();
        }
    });
}




/**
 * Действия при загрузке страницы
 */
$(window).load(function(){
    $("#logLoader").hide();

    // Старт отчёта
    $("#beginBtn").click(function(){
        $(".notice-row").hide();
        $("#beginBtn").hide();
        $("#logLoader").show();
        $("#logPre").text('');

        logLine('# Импорт исходного списка айдишников');

        // Выполняем первый шаг
        nextStep('openBase');
    })
});