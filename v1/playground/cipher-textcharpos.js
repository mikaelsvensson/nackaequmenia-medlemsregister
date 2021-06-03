(function () {
    function getDictionary(keyText) {
        var charMap = {};
        var charPos = 1;
        var rowPos = 1;
        var lastWasNewLine = false;
        for (var i = 0; i < keyText.length; i++) {
            var char = keyText[i];
            if (char == '\n') {
                if (!lastWasNewLine) {
                    charPos = 1;
                    rowPos++;
                }
                lastWasNewLine = true;
            } else {
                if (char != '' && char != ' ') {
                    if (charMap[char]) {
                        charMap[char].push(rowPos + ":" + charPos);
                    } else {
                        charMap[char] = [rowPos + ":" + charPos];
                    }
                    charPos++;
                }
                lastWasNewLine = false;
            }
        }
        return charMap;
    }

    function updateCipherText(sourceText, dictionary) {
        var cipherHtml = [];
        var errors = [];
        for (var i = 0; i < sourceText.length; i++) {
            var sourceChar = sourceText[i];
            if (sourceChar == ' ') {
                //cipherHtml.push(' ');
            } else if (sourceChar == '\n') {
                cipherHtml.push("<br>");
            } else {
                var cipherOpts = dictionary[sourceChar];
                if (cipherOpts) {
                    cipherHtml.push(cipherOpts[Math.floor(Math.random() * cipherOpts.length)]);
                } else {
                    errors.push("Kunde inte kryptera " + sourceChar);
                }
            }
        }
        $("#cipher-text").html(cipherHtml.join(" "));
        $("#cipher-errors").html(errors.join("<br>"));
    }

    $(document).ready(function () {

        var keyPresets = {
            "Kårropet": "ÅHHH NACKA GÖR MAN HÖNS OCH TUPPAR\n" +
            "HÖR NU NÄR DE BÄSTA ROPAR\n" +
            "NACKA SCOUT\n" +
            "\n" +
            "VI ÄR NYA TIDENS SCOUTER\n" +
            "VI GÅR PÅ TRÅDLÖS ROUTER\n" +
            "NACKA SCOUT\n" +
            "\n" +
            "IFRÅN BJÖRKNÄS KOMMER VI\n" +
            "SCOUTERNA MED RUTER I\n" +
            "VI TRIVS BÄST PÅ STORA ÄNGAR\n" +
            "PLATT PÅ MAGEN UTAN SÄNGAR\n" +
            "VI KOMMER NU\n" +
            "NACKA SMU",

            "Kaesslerropet": "" +
            "VI ÄR KAESSLER.\n" +
            "VI FRYSER.\n" +
            "VI HATAR ATT GÅ.\n" +
            "VI KAN INTE LAGA MAT,\n" +
            "MEN VI ÄR HÄR ÄNDÅ.\n" +
            "\n" +
            "VI ÄR KAESSLER.\n" +
            "VI ÄR ROSA.\n" +
            "ABSOLUT INTE GRÅ\n" +
            "VI KOMMER ALLTID FÖR SENT,\n" +
            "MEN VI ÄR HÄR ÄNDÅ.\n" +
            "\n" +
            "VI ÄR KAESSLER.\n" +
            "VI KLAGAR.\n" +
            "VI GNÄLLER SOM FÅ.\n" +
            "VI HÖRS PÅ LÅNGT HÅLL,\n" +
            "MEN VI ÄR HÄR ÄNDÅ."
        }

        var alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZÅÄÖ";

        var key = "";

        var onKeyTextChanged = function () {
            updateCipherText(
                $("#source-text").val().toLocaleUpperCase(),
                getDictionary(
                    $("#key-text").val().toLocaleUpperCase()
                )
            );
        };

        $("#key-text").change(onKeyTextChanged);

        Object.keys(keyPresets).forEach(function (label) {
            $("#key-text-presets").append($("<button/>").attr("type", "button").click(function () {
                $("#key-text").val(keyPresets[label]);
                onKeyTextChanged();
            }).text(label));
        })

        $("#source-text").on("input", onKeyTextChanged)
    });
})();