async function checkLinkWithProxy(url) {
  // Wrap the target URL inside the proxy API URL
  const proxyUrl = `https://api.allorigins.win/get?url=${encodeURIComponent(url)}`;
  
  try {
    const response = await fetch(proxyUrl);
    if (!response.ok) throw new Error("Network response was not ok.");
    
    const data = await response.json();
    
    // AllOrigins returns the target's HTTP status code in its data object
    if (data.status.http_code >= 200 && data.status.http_code < 400) {
      document.getElementById("result").innerText = `Link is active! Status: ${data.status.http_code}`;
    } else {
      document.getElementById("result").innerText = `Broken link. Status: ${data.status.http_code}`;
    }
  } catch (error) {
    document.getElementById("result").innerText = "Error: Could not process the link check.";
  }
}