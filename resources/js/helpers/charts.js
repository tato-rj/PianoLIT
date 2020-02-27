convertHex = function(hex,opacity){
    hex = hex.replace('#','');
    r = parseInt(hex.substring(0,2), 16);
    g = parseInt(hex.substring(2,4), 16);
    b = parseInt(hex.substring(4,6), 16);

    result = 'rgba('+r+','+g+','+b+','+opacity/100+')';
    return result;
}

arraySum = function (total, num) {
  return total + num;
}

getStepSize = function(records) {
    return Math.ceil(Math.max.apply(Math, records)/10);
}

// createLineChart = function(type) {
//     var chart = document.getElementById(type+"-chart");
//     var ctx = chart.getContext('2d');
//     var activeRecords = JSON.parse(chart.getAttribute('data-records'));
//     // var deletedRecords = JSON.parse(chart.getAttribute('data-deleted-records'));

//     var activeData = [];
//     // var deletedData = [];
//     var fields = [];

//     for (var i = 0; i < activeRecords.length; i++) {
//         if (type == 'day') {
//             fields.push(activeRecords[i].month+" "+activeRecords[i].day);
//         } else if (type == 'month') {
//             fields.push(activeRecords[i].month);
//         } else {
//             fields.push(activeRecords[i].year);
//         }

//         activeData.push(activeRecords[i].count);
//         // deletedData.push(deletedRecords[i].count);
//     }

//     var myChart = new Chart(ctx, {
//         type: 'line',
//         data: {
//             labels: fields,
//             datasets: [
//             {
//                 label: 'New sign ups',
//                 data: activeData,
//                 pointBackgroundColor: 'rgba(52, 144, 220, 0.2)',
//                 pointBorderColor: 'rgba(52, 144, 220, 1)',
//                 backgroundColor: 'rgba(52, 144, 220, 0.2)',
//                 borderColor: 'rgba(52,144,220,1)',
//                 borderWidth: 1
//             },
//             // {
//             //     label: 'Deleted accounts',
//             //     data: deletedData,
//             //     pointBackgroundColor: 'rgba(158, 158, 158, 0.2)',
//             //     pointBorderColor: 'rgba(158, 158, 158, 1)',
//             //     backgroundColor: 'rgba(158, 158, 158, 0.2)',
//             //     borderColor: 'rgba(158, 158, 158, 1)',
//             //     borderWidth: 1
//             // }
//         ]},
//         options: {
//             scales: {
//                 yAxes: [{
//                     ticks: {
//                         min: 0,
//                         beginAtZero: true,
//                         callback: function(value, index, values) {
//                             if (Math.floor(value) === value) {
//                                 return value;
//                             }
//                         }
//                     }
//                 }]
//             }
//         }
//     }); 
// }