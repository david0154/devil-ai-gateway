


Overview


Devil AI is powered by Ollama and accessible through:


https://aiapi.devilpvt.in


Model:


- devil-ai
- gemma3:4b


---


API Endpoint


Generate Response


POST


https://aiapi.devilpvt.in/api/generate


Headers:


Content-Type: application/json


Request:


{
  "model": "devil-ai",
  "prompt": "Hello Devil AI",
  "stream": false
}


Response:


{
  "response": "Hello! How can I help you today?"
}


---


List Available Models


GET


https://aiapi.devilpvt.in/api/tags


Response:


{
  "models": [
    {
      "name": "devil-ai:latest"
    },
    {
      "name": "gemma3:4b"
    }
  ]
}


---


PHP Integration


Create file:


<?php


$message = $_POST['message'] ?? '';


$data = [
    "model" => "devil-ai",
    "prompt" => $message,
    "stream" => false
];


$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, "https://aiapi.devilpvt.in/api/generate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);


curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


$response = curl_exec($ch);


curl_close($ch);


echo $response;


?>


---


Simple PHP Chat Page


index.php


<!DOCTYPE html>
<html>
<head>
    <title>Devil AI Chat</title>
</head>
<body>


<h2>Devil AI</h2>


<form method="POST">
    <input type="text"
           name="message"
           style="width:400px;"
           placeholder="Ask Devil AI">


    <button type="submit">
        Send
    </button>
</form>


<?php


if(isset($_POST['message'])){


    $data = [
        "model" => "devil-ai",
        "prompt" => $_POST['message'],
        "stream" => false
    ];


    $ch = curl_init();


    curl_setopt(
        $ch,
        CURLOPT_URL,
        "https://aiapi.devilpvt.in/api/generate"
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            "Content-Type: application/json"
        ]
    );


    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($data)
    );


    $response = curl_exec($ch);


    curl_close($ch);


    $json = json_decode($response, true);


    echo "<hr>";
    echo "<b>You:</b><br>";
    echo htmlspecialchars($_POST['message']);
    echo "<br><br>";


    echo "<b>Devil AI:</b><br>";
    echo nl2br(
        htmlspecialchars(
            $json['response']
        )
    );
}


?>


</body>
</html>


---


JavaScript Integration


async function askDevilAI(message){


    const response = await fetch(
        "https://aiapi.devilpvt.in/api/generate",
        {
            method: "POST",
            headers: {
                "Content-Type":"application/json"
            },
            body: JSON.stringify({
                model:"devil-ai",
                prompt:message,
                stream:false
            })
        }
    );


    const data = await response.json();


    return data.response;
}


---


Android Kotlin Integration


data class OllamaRequest(
    val model:String,
    val prompt:String,
    val stream:Boolean
)


data class OllamaResponse(
    val response:String
)


API:


@POST("api/generate")
suspend fun chat(
    @Body request: OllamaRequest
): OllamaResponse


Base URL:


https://aiapi.devilpvt.in/


---


Flutter Integration


final response = await http.post(
  Uri.parse(
    "https://aiapi.devilpvt.in/api/generate"
  ),
  headers: {
    "Content-Type":"application/json"
  },
  body: jsonEncode({
    "model":"devil-ai",
    "prompt":"Hello",
    "stream":false
  }),
);


final data = jsonDecode(response.body);


print(data["response"]);


---


Developer Information


AI Name:


Devil AI


Developer:


David


Company:


Devil One Pvt Ltd


Website:


https://devilone.in


GitHub:


https://github.com/david0154


---


Production Recommendations


- Enable API keys
- Add Rate Limiting
- Restrict CORS
- Add Request Logging
- Add User Authentication
- Add Conversation Storage
- Add Search & Tool Calling Layer


Current Architecture:


Frontend → aiapi.devilpvt.in → Ollama → Devil AI 
