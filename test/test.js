let fetch_timeout = null;

async function RunTest() {
  document.getElementById("button").disabled = true;
  ClearStatus();
  CheckLink();
  FetchDictionary();
}

function CheckLink() {
  let status = "Failed";
  if (document.createElement('link').relList.supports('compression-dictionary')) {
    status = "&#x2705; OK";
  }
  document.getElementById("link").innerHTML = status;
}

function FetchFailed() {
  document.getElementById("fetch").innerHTML = '&#x274c; Failed';
  TestDone();
}

function FetchOK() {
  if (fetch_timeout) {
    clearTimeout(fetch_timeout);
    fetch_timeout = null;
  }
  document.getElementById("fetch").innerHTML = "&#x2705; OK";
  setTimeout(TestFetch, 1000);
}

function FetchDictionary() {
  var link = document.createElement('link');
  link.rel = 'compression-dictionary';
  document.head.appendChild(link);
  link.href = 'dictionary.php';
  fetch_timeout = setTimeout(FetchFailed, 10000);
}

function ClearStatus() {
  document.getElementById("link").innerText = '';
  document.getElementById("fetch").innerText = '';
  document.getElementById("dcb").innerText = '';
  document.getElementById("dcz").innerText = '';
}

async function TestDone() {
  document.getElementById("button").disabled = false;
}

const observer = new PerformanceObserver((list) => {
  list.getEntries().forEach((entry) => {
    const file = entry.name.split('/').pop();
    console.log(file);
    if (file == 'dictionary.php') {
      FetchOK();
    }
  });
});

async function TestFetch() {
  await FetchDCB('dcb');
  await FetchDCB('dcz');
  TestDone();
}

async function FetchDCB(type) {
  const response = await(fetch('compressed.php?f=' + type));
  let status = "&#x274c; Failed";
  if (response.ok) {
    const text = await response.text();
    if (text == "OK") {
      status = "&#x2705; OK";
    } else {
      status = "&#x274c; " + text;
    }
  } else {
    status = "&#x274c; Fetch failed";
  }
  document.getElementById(type).innerHTML = status;
}

observer.observe({ type: "resource", buffered: true });