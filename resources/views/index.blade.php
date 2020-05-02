<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>教育研究调查</title>

    <link rel="stylesheet" href="/weui.css"/>
    <link rel="stylesheet" href="/play.css?v5"/>
</head>
<body ontouchstart>
<div class="container" id="container">
    <div class="page__hd">
        <div id="play_desc" class="page__desc"></div>
    </div>
    <div id="list" class="center-in-center">
        <div class="picture" id="countdown"></div>
        <div class="picture" id="pos1"></div>
        <div class="picture" id="pos2"></div>
        <div class="picture" id="pos3"></div>
    </div>
    <div class="page flex">
        <div class="page__ft j_bottom">
            <div class="weui-flex">
                <div class="weui-flex__item">
                    <a href="javascript:;" id="left" class="weui-btn weui-btn_primary weui-btn_disabled btn"><span>🐟</span></a>
                </div>
                <div class="weui-flex__item">
                    <a href="javascript:;" id="right" class="weui-btn weui-btn_primary weui-btn_disabled btn"><span class="flip">🐟</span></a>
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="ok-tip">
    <source = src="/ok.mp3" type="audio/mp3">
</audio>
<audio id="bad-tip">
    <source = src="/bad.mp3" type="audio/mp3">
</audio>

<script src="/zepto.min.js"></script>
<script type="text/javascript">
    $(function () {
        countdown(3);
        // getData();
    });

    Date.prototype.Format = function (fmt) {
        var o = {
            "m+": this.getMonth() + 1, //月份
            "d+": this.getDate(), //日
            "h+": this.getHours(), //小时
            "i+": this.getMinutes(), //分
            "s+": this.getSeconds(), //秒
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度
            "S": this.getMilliseconds() //毫秒
        };
        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length))
        for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)))
        return fmt;
    }

    function countdown($count) {
        if ($count <= 0) {
            $('#countdown').html('GO');
            $('#countdown').animate({
              opacity: 0,
              scale:1.3,
            }, 500, 'ease-out');
            setTimeout(function () {
                $('#countdown').html('');
                getData();
            }, 1000);
            return;
        }
        $('#countdown').html($count);
        $count--;
        setTimeout(function () {
            countdown($count);
        }, 1000);
    }

    var $currentRound = 1;
    var $currentStep = 0;
    var $d1Time = 0;
    var $RTTime = 0;
    var $RTStart = 0;
    var $RTEnd = 0;
    var $startTime = 0;

    var $settings = {};

    var $oneRoundCost = {};
    var $oneRoundAws = {};

    function getData() {
        $.get('/data', function (response) {
            var $data = response;
            start($data);
        });
    }

    function start($data) {
        var $roundList = $data.roundList;
        var $guideList = $data.guideList;
        var $goalList = $data.goalList;
        var $correctMap = $data.correctMap;

        $settings = $data.settings;

        var $submitData = [];

        var $audioOk = document.getElementById("ok-tip");
        var $audioBad = document.getElementById("bad-tip");

        play();

        $('#left').click(function(event) {
            if (4 != $currentStep) return;
            // console.log('你点击了右,$currentRound='+$currentRound+'$currentStep='+$currentStep);
            action($currentRound, 1, $RTTime);
        });
        $('#right').click(function(event) {
            if (4 != $currentStep) return;
            // console.log('你点击了左,$currentRound='+$currentRound+'$currentStep='+$currentStep);
            action($currentRound, 2, $RTTime);
        });

        function enabled() {
            $('#left').removeClass('weui-btn_disabled');
            $('#right').removeClass('weui-btn_disabled');
        }

        function disable() {
            $('#left').addClass('weui-btn_disabled');
            $('#right').addClass('weui-btn_disabled');
        }

        function action($currentRound, $answer, $costTime) {
            $oneRoundAws = {
                'answer': $answer,
                'cost_time': $costTime,
            };
            hook($currentRound, 4, $costTime ? $costTime : $settings.t3);
            $RTEnd = Date.now();
            $RTTime = $RTEnd - $RTStart;

            disable();
            play();

            $round = $roundList[$currentRound - 1];
            if ($answer == $correctMap[$goalList[$round.goalId][1]]) {
                // console.log('对！');
                $audioOk.play();
            } else {
                // console.log('错！');
                $audioBad.play();
            }
        }

        function gameover() {
            // console.log('submit', $submitData);
            $.ajax({
                type: 'POST',
                url: '/submit',
                data: JSON.stringify($submitData),
                contentType: 'application/json',
                success: function(data) {
                    setTimeout(function () {
                        window.location.href = '/success';
                    }, 1000);
                }
            });
        }

        function play() {
            $currentStep++;
            if ($currentStep > 5) {
                $currentStep = 1;
                $currentRound++;
            }

            if ($currentRound > $roundList.length) {
                gameover();
                return;
            }

            $timeOut = 0;
            var $round = $roundList[$currentRound - 1];
            switch ($currentStep) {
                case 1:
                    $timeOut = step1();
                    break;
                case 2:
                    $timeOut = step2($guideList[$round.guideId]);
                    break;
                case 3:
                    $timeOut = step3();
                    break;
                case 4:
                    // console.log($round);
                    $RTStart = Date.now();
                    $RTEnd = 0;
                    $timeOut = step4($goalList[$round.goalId]);
                    enabled();

                    break;
                case 5:
                    $timeOut = step5();
                    break;
            }

            $('#play_desc').html('步骤:' + $currentStep + ' 回合:'+ $currentRound + ' 停留毫秒:' + ($timeOut ? $timeOut : '--'));

            if (4 == $currentStep) {
                setTimeout(function() {
                    if (!$RTEnd) {
                        action($currentRound, 0, 0);
                    }
                }, $settings.t3);
            } else {
                hook($currentRound, $currentStep, $timeOut);
                setTimeout(play, $timeOut);
            }
        }

        function step1() {
            // console.log('time:'+new Date().Format('hh:ii:ss')+' gap:'+(Date.now()-$startTime)+' round:'+$currentRound);
            $startTime = Date.now();

            $('#pos1').html('&nbsp');
            $('#pos2').html('<span>✚</span>');
            $('#pos3').html('&nbsp');

            $d1Time = getRandomInt(400, 1600);

            return $d1Time;
        }

        function step2($guide) {
            $('#pos1').html(drawGuide($guide[0]));
            $('#pos2').html(drawGuide($guide[1]));
            $('#pos3').html(drawGuide($guide[2]));

            return $settings.t1;
        }

        function step3() {
            $('#pos1').html('&nbsp');
            $('#pos2').html('<span>✚</span>');
            $('#pos3').html('&nbsp');

            return $settings.t2;
        }

        function step4($goalInfo) {
            var $pos = $goalInfo[0];
            var $result = drawGoal($goalInfo[1]);

            if ($pos) {
                $('#pos1').html('&nbsp');
                $('#pos2').html('<span>✚</span>');
                $('#pos3').html($result);
            } else {
                $('#pos1').html($result);
                $('#pos2').html('<span>✚</span>');
                $('#pos3').html('&nbsp');
            }

            return 0;
        }

        function step5() {
            $('#pos1').html('&nbsp');
            $('#pos2').html('<span>✚</span>');
            $('#pos3').html('&nbsp');

            // console.log('t1='+$d1Time+' t2='+$RTTime+' t3='+(3500 - $RTTime - $d1Time - 500));

            // 3500 - $RTTime - $d1Time - 500
            return Math.max(3500 - (Date.now() - $startTime), 0);
        }

        function drawGuide($guideId) {
            var $result = '&nbsp';
            switch ($guideId) {
                case 1:
                    $result = '<span>✚</span>';
                    break;
                case 2:
                    $result = '<span>✻</span>';
                    break;
            }

            return $result;
        }

        function drawGoal($goal) {
            var $result = '';
            switch ($goal) {
                case 1:
                    $result = `<span class="flip">🐟🐟🐟🐟🐟</span>`;
                    break;
                case 2:
                    $result = `<span>🐟🐟🐟🐟🐟</span>`;
                    break;
                case 3:
                    $result = `<span class="flip">🐟🐟</span><span>🐟</span><span class="flip">🐟🐟</span>`;
                    break;
                case 4:
                    $result = `<span>🐟🐟</span><span class="flip">🐟</span><span>🐟🐟</span>`;
                    break;
            }

            return $result;
        }

        function getRandomInt(min, max) {
            min = Math.ceil(min);
            max = Math.floor(max);

            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        function hook($currentRound, $currentStep, $timeCost)
        {
            if ($currentStep == 1) {
                $oneRoundCost = {};
                $oneRoundAws = {};
            }

            $oneRoundCost[$currentStep] = $timeCost;

            if ($currentStep == 5) {
                $round = $roundList[$currentRound - 1];
                $submitData.push({
                    'round': $currentRound,
                    'guideId': $round.guideId,
                    'goalId': $round.goalId,
                    'answer': $oneRoundAws.answer,
                    'cost_time': $oneRoundAws.cost_time,
                    'time_details': $oneRoundCost,
                });
            }
        }
    }
</script>
</body>
</html>
