<?php

namespace App\Http\Controllers;

use App\Data;
use App\Exports\DataExport;
use App\Exports\DataRawExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        if (!session('student_no')) {
            return response()->redirectTo('/login');
        }

        return view('index');
    }

    public function quickPlay(Request $request)
    {
        return view('index');
    }

    public function data(Request $request)
    {
        $settings = Redis::hgetall('settings');
        $settings = [
            't_guide' => $settings['t_guide'] ?? 100,
            't_interval' => $settings['t_interval'] ?? 400,
            't_rt_max' => $settings['t_rt_max'] ?? 1700,
            'n' => $settings['n'] ?? 10,
            't_total' => $settings['t_total'] ?? 3500
            // 'nn' => $settings['nn'] ?? 0,
        ];

        /**
         * 位置：(上、中、下)
         * 形态：(0：空白、1：+、2：*)
         */
        $guideList = [
            1 => [0, 1, 0], // 无线索
            2 => [0, 2, 0], // 中央线索
            3 => [2, 1, 2], // 双线索

            4 => [2, 1, 0], // 空间线索-上
            5 => [0, 1, 2], // 空间线索-下
        ];
        $goalList = [
            1 => [0, 1], // 上-右-一致
            2 => [1, 1], // 下-右-一致

            3 => [0, 2], // 上-左-一致
            4 => [1, 2], // 下-左-一致

            5 => [0, 3], // 上-左-不一致
            6 => [1, 3], // 下-左-不一致

            7 => [0, 4], // 上-右-不一致
            8 => [1, 4], // 下-右-不一致
        ];
        $correctMap = [
            1 => 2, // 右
            2 => 1, // 左
            3 => 1, // 左
            4 => 2, // 右
        ];

        $situations = [
            ['guideId' => 1, 'goalId' => 1],
            ['guideId' => 1, 'goalId' => 2],
            ['guideId' => 1, 'goalId' => 3],
            ['guideId' => 1, 'goalId' => 4],
            ['guideId' => 1, 'goalId' => 5],
            ['guideId' => 1, 'goalId' => 6],
            ['guideId' => 1, 'goalId' => 7],
            ['guideId' => 1, 'goalId' => 8],

            ['guideId' => 1, 'goalId' => 1],
            ['guideId' => 1, 'goalId' => 2],
            ['guideId' => 1, 'goalId' => 3],
            ['guideId' => 1, 'goalId' => 4],
            ['guideId' => 1, 'goalId' => 5],
            ['guideId' => 1, 'goalId' => 6],
            ['guideId' => 1, 'goalId' => 7],
            ['guideId' => 1, 'goalId' => 8],

            ////////////////////////////////

            ['guideId' => 2, 'goalId' => 1],
            ['guideId' => 2, 'goalId' => 2],
            ['guideId' => 2, 'goalId' => 3],
            ['guideId' => 2, 'goalId' => 4],
            ['guideId' => 2, 'goalId' => 5],
            ['guideId' => 2, 'goalId' => 6],
            ['guideId' => 2, 'goalId' => 7],
            ['guideId' => 2, 'goalId' => 8],

            ['guideId' => 2, 'goalId' => 1],
            ['guideId' => 2, 'goalId' => 2],
            ['guideId' => 2, 'goalId' => 3],
            ['guideId' => 2, 'goalId' => 4],
            ['guideId' => 2, 'goalId' => 5],
            ['guideId' => 2, 'goalId' => 6],
            ['guideId' => 2, 'goalId' => 7],
            ['guideId' => 2, 'goalId' => 8],

            ////////////////////////////////

            ['guideId' => 3, 'goalId' => 1],
            ['guideId' => 3, 'goalId' => 2],
            ['guideId' => 3, 'goalId' => 3],
            ['guideId' => 3, 'goalId' => 4],
            ['guideId' => 3, 'goalId' => 5],
            ['guideId' => 3, 'goalId' => 6],
            ['guideId' => 3, 'goalId' => 7],
            ['guideId' => 3, 'goalId' => 8],

            ['guideId' => 3, 'goalId' => 1],
            ['guideId' => 3, 'goalId' => 2],
            ['guideId' => 3, 'goalId' => 3],
            ['guideId' => 3, 'goalId' => 4],
            ['guideId' => 3, 'goalId' => 5],
            ['guideId' => 3, 'goalId' => 6],
            ['guideId' => 3, 'goalId' => 7],
            ['guideId' => 3, 'goalId' => 8],

            ////////////////////////////////

            ['guideId' => 4, 'goalId' => 1],
            ['guideId' => 4, 'goalId' => 1],
            ['guideId' => 4, 'goalId' => 3],
            ['guideId' => 4, 'goalId' => 3],
            ['guideId' => 4, 'goalId' => 5],
            ['guideId' => 4, 'goalId' => 5],
            ['guideId' => 4, 'goalId' => 7],
            ['guideId' => 4, 'goalId' => 7],

            ['guideId' => 5, 'goalId' => 2],
            ['guideId' => 5, 'goalId' => 2],
            ['guideId' => 5, 'goalId' => 4],
            ['guideId' => 5, 'goalId' => 4],
            ['guideId' => 5, 'goalId' => 6],
            ['guideId' => 5, 'goalId' => 6],
            ['guideId' => 5, 'goalId' => 8],
            ['guideId' => 5, 'goalId' => 8],
        ];

        $roundList = [];
        for ($i=0; $i < $settings['n']; $i++) {
            $roundList[] = $situations;
        }
        $roundList = array_merge(...$roundList);
        shuffle($roundList);

        if (!empty($settings['nn'])) {
            $roundList = array_slice($roundList, 0, min($settings['nn'], $settings['n'] * 40));
        }

        return [
            'guideList' => $guideList,
            'goalList' => $goalList,
            'correctMap' => $correctMap,
            'roundList' => $roundList,
            'settings' => $settings,
        ];
    }

    public function submit(Request $request)
    {
        $data = [
            'school' => session('school', ''),
            'class' => session('class', ''),
            'name' => session('name', 'quick_test'),
            'grade' => session('grade', ''),
            'age' => session('age', 0),
            'student_no' => session('student_no', ''),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'data' => $request->all(),
        ];

        $result = Data::create($data);

        session(['last_id' => $result->id]);
    }

    public function lastResult(Request $request)
    {
        $result = Data::where('id', $request->id ?? session('last_id'))->first();

        if (!$result) return;

        return view('result', ['result' => $result]);
    }

    public function exportRaw($start = null, $end = null)
    {
        $end = $end ?? $start;

        $filter = [
            'start' => $start,
            'end' => $end,
        ];

        return (new DataRawExport($filter))->download('data_raw.xlsx');
    }

    public function export($start = null, $end = null)
    {
        $end = $end ?? $start;

        $filter = [
            'start' => $start,
            'end' => $end,
        ];

        return (new DataExport($filter))->download('data.xlsx');
    }

    public function success(Request $request)
    {
        $count = 0;
        if (session('student_no')) {
            $count = Data::where([
                'name' => session('name'),
                'class' => session('class'),
                'student_no' => session('student_no'),
                'grade' => session('grade'),
                'school' => session('school'),
            ])->count();
        }

        return view('success', [
            'name' => session('name'),
            'count' => $count,
        ]);
    }
}
