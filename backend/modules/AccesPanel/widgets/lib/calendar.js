$(document).ready(function() {
    var loading=$('#loading');
    var lastJQueryTS = 0 ;
    
            $("#crm").on('change','.ajax-calendar',function (e){
                var send = true;
                if (typeof(e) == 'object'){
                    if (e.timeStamp - lastJQueryTS < 300)   send = false;
                    lastJQueryTS = e.timeStamp;
                }
                if (send){
                    loading.fadeIn(500);
                    var formData = $('#calendarForm');
                  
                    var events = {
                        data:{filter:formData.serializeArray()},
                        type: 'POST',
                        url : '/crm/calendar/load'
                    };
                    $('#calendar').fullCalendar('removeEvents');
                    $('#calendar').fullCalendar( 'removeEventSource', events );
                    $('#calendar').fullCalendar('addEventSource', events);   
                    $('#calendar').fullCalendar('rerenderEvents' ); 
                    loading.fadeOut(500);
                }     
            });
            
            $("#crm").on('click','.calendarHelp',function (e){
                $(this).toggleClass('active');
            });
    
            $('#calendar').fullCalendar({
                axisFormat: 'H:mm',
                timeFormat: "H:mm",
                columnFormat:'ddd DD.MM',
                
                header: {
                    left: 'prev,next today',
                    center: 'prevYear title nextYear ',
                    right: 'month,agendaWeek,agendaDay'
                },
                monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                monthNamesShort: ['Янв.','Фев.','Март','Апр.','Май','Июнь','Июль','Авг.','Сент.','Окт.','Ноя.','Дек.'],
                dayNames: ["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
                dayNamesShort: ["ВС","ПН","ВТ","СР","ЧТ","ПТ","СБ"],
                buttonText: {
                    today: "Сегодня",
                    month: "Месяц",
                    week: "Неделя",
                    day: "День"
                },
                allDayText:'Весь день',
                
                firstDay: 1,
                defaultView: 'agendaWeek',
                selectable: true,
                selectHelper: true,
                editable: true,

                events: function(start, end, timezone, callback) {
                        loading.fadeIn(500);
                        var formData = $('#calendarForm');
                        $.post('/crm/calendar/load',{start:start.valueOf()/1000,end:end.valueOf()/1000,filter:formData.serializeArray()}).done(function(result){
                            var events = [];
                            result.forEach(function(item, i, arr) {
                                events.push({
                                    id: item.id,
                                    title: item.title,
                                    start: item.start,
                                    end: item.end,
                                    backgroundColor: item.bgColor,
                                    textColor:'black'
                                });
                            });
                            $('#calendar').fullCalendar('removeEvents');
                            $('#calendar').fullCalendar( 'removeEventSource', events );
                            callback(events);
                            loading.fadeOut(500);
                        });
                },
                
                eventClick: function(calEvent, jsEvent, view) {
                    loading.fadeIn(300);
                    
                    var path=null;
                    if(calEvent.id) path='/crm/agents/task?id='+calEvent.id;
                    else path='/crm/agents/task?start='+calEvent.start.valueOf()/1000+'&end='+calEvent.end.valueOf()/1000;
                    
                    var modalTarget=$('#task');
                    modalTarget.find('.modal-body').load(path,function(){
                        if(modalTarget.find('.selectColor').length>0) modalTarget.find('.selectColor').selectbox();
                        loading.fadeOut(300);
                        modalTarget.modal('show');
                    });
                    return false;
                },
                
                eventResize : function(event, dayDelta, revertFunc) {
                    loading.fadeIn(300);
                    $.post('/crm/calendar/updatedate?id='+event.id,{start:event.start.valueOf()/1000,end:event.end.valueOf()/1000}).done(function(result){
                        loading.fadeOut(300);
                    });
                },
                eventDrop: function(event, dayDelta, revertFunc) {
                    loading.fadeIn(300);
                    $.post('/crm/calendar/updatedate?id='+event.id,{start:event.start.valueOf()/1000,end:event.end.valueOf()/1000}).done(function(result){
                        loading.fadeOut(300);
                    });
                },



            });
});