function getCookie(name) {
    if (!name) return;
    let matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, "\\$1") + "=([^;]*)"));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}
function getUrlParam(name) {
    if (!name) return;
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const paramValue = urlParams.get(name);
    return paramValue;
}
function sendRequest(url, type = "GET", param = {}, func = null, atr = {}) {
    let xhr = new XMLHttpRequest();
    xhr.open(type, url);
    xhr.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            try {
                let result = JSON.parse(this.responseText);
                console.log("API запрос", result);
                if (func) {
                    func(result, atr);
                }
            } catch {
                console.error("error parse", this.responseText);
            }
        } else if (this.readyState === 4 && this.status === 401) {
            console.error("Failed authorization");
            if (thisPage.name != "login") {
                location.href = thisPage.url + "/login";
            }
        } else if (this.readyState === 4) {
            console.error("Fatal Error!!!");
        }
    };
    xhr.send(JSON.stringify(param));
}
function enumerate(num, dec) {
    if (num > 100) num = num % 100;
    if (num <= 20 && num >= 10) return dec[2];
    if (num > 20) num = num % 10;
    return num === 1 ? dec[0] : num > 1 && num < 5 ? dec[1] : dec[2];
}
function mDate(timestamp) {
    var date = new Date(timestamp * 1000);
    var options = {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "numeric",
    };
    return date.toLocaleString("ua", options);
}

if (document.readyState !== "loading") {
    preload();
} else {
    document.addEventListener("DOMContentLoaded", preload);
}

// Запуск обробника сторінки
function preload() {
    sendRequest("settings.php", "GET", "", function (result) {
        document.getElementsByClassName("n-loader")[0].classList.add("hidden");
        var connectState = true;
        try {
            if (result.novaposhta.connect) {
                if ("sender" in result.novaposhta) {
                    document.getElementById("np-sender").innerHTML = `Відправник: ${result.novaposhta.sender.name}<br>Телефон: ${result.novaposhta.sender.phone}<br>${result.novaposhta.sender.cityName}<br>${result.novaposhta.sender.warehouseName}<span onclick="editsender()"><ion-icon name="create-outline"></ion-icon></span>`;
                    document.getElementById("senderRef").value = result.novaposhta.data[0].Ref;
                    document.getElementById("senderContactRef").value = result.novaposhta.data[0].contacts[0].Ref;
                    document.getElementById("senderName").value = `${result.novaposhta.data[0].contacts[0].FirstName} ${result.novaposhta.data[0].contacts[0].LastName}`;
                    document.getElementById("senderPhone").value = result.novaposhta.data[0].contacts[0].Phones;
                } else {
                    document.getElementById("np-sender").innerHTML = `Необхідно налаштувати дані відправника`;
                    document.getElementById("senderRef").value = result.novaposhta.data[0].Ref;
                    document.getElementById("senderContactRef").value = result.novaposhta.data[0].contacts[0].Ref;
                    document.getElementById("senderName").value = `${result.novaposhta.data[0].contacts[0].FirstName} ${result.novaposhta.data[0].contacts[0].LastName}`;
                    document.getElementById("senderPhone").value = result.novaposhta.data[0].contacts[0].Phones;
                    document.getElementsByClassName("setting-form-2")[0].classList.remove("hidden");
                }
            } else {
                connectState = false;
            }
        } catch {
            connectState = false;
        }
        try {
            if (result.smartsender.connect) {
                document.getElementById("ss-project").innerHTML = `Проект: "${result.smartsender.account.name}"`;
            } else {
                connectState = false;
            }
        } catch {
            connectState = false;
        }
        if (!connectState) {
            document.getElementsByClassName("setting-form-1")[0].classList.remove("hidden");
        } else {
            document.getElementsByClassName("setting-view-1")[0].classList.remove("hidden");
        }
    });
}

// Додаткові функції
function keysform() {
    event.preventDefault();
    var apiData = {
        method: "saveApi",
        npKey: document.getElementById("npkey").value,
        ssKey: document.getElementById("sskey").value,
    };
    sendRequest("settings.php", "POST", apiData, function (result) {
        if (result.state) {
            document.getElementById("np-sender").innerHTML = `Необхідно налаштувати дані відправника`;
            document.getElementById("ss-project").innerHTML = `Проект: "${result.smartsender.account.name}"`;
            document.getElementById("senderRef").value = result.novaposhta.data[0].Ref;
            document.getElementById("senderContactRef").value = result.novaposhta.data[0].contacts[0].Ref;
            document.getElementById("senderName").value = `${result.novaposhta.data[0].contacts[0].FirstName} ${result.novaposhta.data[0].contacts[0].LastName}`;
            document.getElementById("senderPhone").value = result.novaposhta.data[0].contacts[0].Phones;
            document.getElementsByClassName("setting-form-2")[0].classList.remove("hidden");
            document.getElementsByClassName("setting-form-1")[0].classList.add("hidden");
            document.getElementsByClassName("setting-view-1")[0].classList.remove("hidden");
        } else {
            alert(result.error.message.join("\n"));
        }
    });
}
function loadCity() {
    document.getElementById("senderNP").innerHTML = "";
    var data = {
        term: document.getElementById("senderCity").value,
    };
    if (data.term == "") {
        document.getElementsByClassName("cityList")[0].innerHTML = "";
        return;
    }
    sendRequest("citysearch.php", "POST", data, function (result) {
        if (result.term == document.getElementById("senderCity").value) {
            var cityList = document.getElementsByClassName("cityList")[0];
            cityList.innerHTML = "";
            var cityElems = document.createElement("div");
            cityElems.className = "cityElems";
            cityList.appendChild(cityElems);
            for (var cc = 0; cc < result.cityes.length; cc++) {
                if (result.cityes[cc].Warehouses >= 1) {
                    var cityElem = document.createElement("div");
                    cityElem.data = result.cityes[cc];
                    cityElem.className = "cityElem";
                    cityElem.innerHTML = result.cityes[cc].Present;
                    cityElems.appendChild(cityElem);
                    cityElem.addEventListener("click", citySelect);
                }
            }
            if (result.cityes.length < 1) {
                var cityElem = document.createElement("div");
                cityElem.className = "cityElem";
                cityElem.innerHTML = "Немає результатів";
                cityElems.appendChild(cityElem);
            }
        }
    });
}
function citySelect() {
    cityData = this.data;
    console.log("data", cityData);
    document.getElementById("senderCity").value = cityData.Present;
    document.getElementsByClassName("cityList")[0].innerHTML = "";
    document.getElementById("cityRef").value = cityData.DeliveryCity;
    sendRequest("cityselect.php", "POST", { cityRef: cityData.DeliveryCity }, function (result) {
        var whSelect = document.getElementById("senderNP");
        var approveWH = false;
        for (var whc = 0; whc < result.wh.length; whc++) {
            if (result.wh[whc].CategoryOfWarehouse == "Branch") {
                approveWH = true;
                var whOpt = document.createElement("option");
                whOpt.innerHTML = result.wh[whc].Description;
                whOpt.value = result.wh[whc].Ref;
                whSelect.appendChild(whOpt);
            }
            if (!approveWH) {
                var whOpt = document.createElement("option");
                whOpt.innerHTML = `Немає підтримуваних відділень місті`;
                whSelect.appendChild(whOpt);
            }
        }
    });
}
function senderform() {
    event.preventDefault();
    var senderData = {
        method: "saveSender",
        phone: document.getElementById("senderPhone").value,
        name: document.getElementById("senderName").value,
        senderRef: document.getElementById("senderRef").value,
        senderContactRef: document.getElementById("senderContactRef").value,
        city: document.getElementById("cityRef").value,
        cityName: document.getElementById("senderCity").value,
        warehouse: document.getElementById("senderNP").value,
        warehouseName: document.querySelector(`[value="${document.getElementById("senderNP").value}"]`).innerHTML,
    };
    sendRequest("settings.php", "POST", senderData, function (result) {
        if (result.state) {
            document.getElementsByClassName("setting-form-2")[0].classList.add("hidden");
            document.getElementById("np-sender").innerHTML = `Відправник: ${result.sender.name}<br>Телефон: ${result.sender.phone}<br>${result.sender.cityName}<br>${result.sender.warehouseName}<span onclick="editsender()"><ion-icon name="create-outline"></ion-icon></span>`;
        } else {
            alert(result.error.message.join("\n"));
        }
    });
}
function editsender() {
    document.getElementsByClassName("setting-form-2")[0].classList.remove("hidden");
}
