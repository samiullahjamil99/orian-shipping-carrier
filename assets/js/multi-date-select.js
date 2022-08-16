function renderCalender(el,month = null,year = null) {
    var d = new Date();
    el.innerHTML = "";
    if (month == null) {
        month = d.getMonth();
    }
    if (year == null) {
        year = d.getFullYear();
    }
    el.dataset.month = month;
    el.dataset.year = year;
    el.dataset.selected = document.getElementById("nonbusiness_days").value;
    var selectedDates = el.dataset.selected;
    if (selectedDates == undefined)
        selectedDates = [];
    else
        selectedDates = selectedDates.split(",");
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const days = ['Su','Mo','Tu','We','Th','Fr','Sa'];
    const daysInMonths = [31,28,31,30,31,30,31,31,30,31,30,31];
    var currentMonthNum = month;
    var currentMonth = months[currentMonthNum];
    var currentYear = year;
    var firstOfMonth = currentYear + "-" + (currentMonthNum + 1) + "-1";
    var firstOfMonthD = new Date(firstOfMonth);
    var calenderHeader = document.createElement("div");
    calenderHeader.classList.add("cal-header");
    el.appendChild(calenderHeader);
    var prevMonthBtn = document.createElement("a");
    prevMonthBtn.href = "javascript:void(0)";
    prevMonthBtn.classList.add("prev-month");
    prevMonthBtn.innerHTML = "&lt";
    prevMonthBtn.addEventListener('click',function() {
        var prevmonth = el.dataset.month;
        var prevyear = el.dataset.year;
        if (prevmonth > 0)
            prevmonth--;
        else {
            prevmonth = 11;
            prevyear--;
        }
        renderCalender(el,prevmonth,prevyear);
    });
    calenderHeader.appendChild(prevMonthBtn);
    var monthTitle = document.createElement("h1");
    monthTitle.classList.add("month");
    monthTitle.innerHTML = currentMonth + " " + currentYear;
    calenderHeader.appendChild(monthTitle);
    var nextMonthBtn = document.createElement("a");
    nextMonthBtn.href = "javascript:void(0)";
    nextMonthBtn.classList.add("next-month");
    nextMonthBtn.innerHTML = "&gt";
    nextMonthBtn.addEventListener('click',function() {
        var nextmonth = el.dataset.month;
        var nextyear = el.dataset.year;
        if (nextmonth < 11)
            nextmonth++;
        else {
            nextmonth = 0;
            nextyear++;
        }
        renderCalender(el,nextmonth,nextyear);
    });
    calenderHeader.appendChild(nextMonthBtn);
    var weekdays = document.createElement("ul");
    weekdays.classList.add("weekdays");
    el.appendChild(weekdays);
    for (var i = 0; i < 7; i++) {
        var weekday = document.createElement('li');
        weekday.innerHTML = days[i];
        weekdays.appendChild(weekday);
    }
    var dates = document.createElement("ul");
    dates.classList.add("dates");
    el.appendChild(dates);
    for (var i = 0; i < firstOfMonthD.getDay(); i++) {
        var day = document.createElement('li');
        dates.appendChild(day);
    }
    var daysinmonth = daysInMonths[currentMonthNum];
    if (currentYear % 4 == 0 && currentMonthNum == 1)
        daysinmonth = 29;
    var tempday = firstOfMonthD.getDay();
    for (var i = 1; i <= daysinmonth; i++) {
        var day = document.createElement('li');
        day.innerHTML = i;
        day.dataset.full = ("0"+i).slice(-2) +'/' + ("0"+(currentMonthNum+1)).slice(-2)+'/' + currentYear;
        dates.appendChild(day);
        var dateIndex = selectedDates.indexOf(day.dataset.full);
        if (dateIndex > -1)
            day.classList.add("selected");
        if (tempday == 5 || tempday == 6) {
            day.classList.add("selected");
            day.classList.add("disabled");
        }
        tempday++;
        if (tempday > 6)
            tempday = 0;
        if (!day.classList.contains("disabled")) {
        day.addEventListener("click",function() {
            var selectedIndex = selectedDates.indexOf(this.dataset.full);
            if (selectedIndex > -1) {
                selectedDates.splice(selectedIndex,1);
                this.classList.remove("selected");
            } else {
                selectedDates.push(this.dataset.full);
                this.classList.add("selected");
            }
            el.dataset.selected = selectedDates;
            document.getElementById("nonbusiness_days").value = selectedDates.toString();
        });
        }
    }
}
jQuery(document).ready(function() {
    var element = document.querySelector(".multi-date-select");
    renderCalender(element);
});