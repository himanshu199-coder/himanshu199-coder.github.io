async function checkLink(url) {
  try {
    // This replaces PHP's cURL request
    const response = await fetch(url);
    
    if (response.ok) {
      document.getElementById("result").innerText = `Success! Status: ${response.status}`;
    } else {
      document.getElementById("result").innerText = `Broken link. Status: ${response.status}`;
    }
  } catch (error) {
    document.getElementById("result").innerText = "Error reaching the URL.";
  }
}

// Example of attaching the function to an HTML button
document.getElementById("checkButton").addEventListener("click", () => {
  const userUrl = document.getElementById("urlInput").value;
  checkLink(userUrl);
});