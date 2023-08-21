const run_step = async function() {
  const response = await fetch("run.php?id=" + id);
  if (response.ok) {
    const result = await response.json();
    let status = '';
    if (result['error']) {
      status = result['error'];
      info['done'] = true;
    } else if (result['status']) {
      status = result['status'];
    }
    if (result['done']) {
      info['done'] = true;
    }
    document.getElementById('status').textContent = status;
  } else {
    console.log("Error: " + response.status);
  }
  if (info['done']) {
    window.location.replace("result.php?id=" + id);
  } else {
    setTimeout(run_step, 1);
  }
}

setTimeout(run_step, 1);
