Sub OnAcceptMessage(oClient, oMessage)

    Dim body

    body = LCase(oMessage.Body)

    If InStr(body,"http://") > 0 Or InStr(body,"https://") > 0 Then

        WritePhishingLog "URL"

    End If

End Sub


Sub WritePhishingLog(strText)

    Dim fso
    Dim file

    Set fso = CreateObject("Scripting.FileSystemObject")

    Set file = fso.OpenTextFile("C:\Program Files (x86)\hMailServer\Logs\phishing.log",8,True)

    file.WriteLine Now & " | " & strText

    file.Close

End Sub