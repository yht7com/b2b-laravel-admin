<?php

namespace App\Admin\Controllers;

use App\Models\Mall;
use App\Models\Categorie;
use App\Models\Area;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Facades\Auth;
use App\Admin\Extensions\Tools\ReleasePost;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;

class MallController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '商城列表';

     protected function grid()
    {
       
        $grid = new Grid(new Mall);
        //隐藏查看按钮
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

         $grid->tools(function ($tools) {
            $tools->batch(function (Grid\Tools\BatchActions $batch) {
                $batch->add('通过审核', new ReleasePost(1));
                $batch->add('拒绝审核', new ReleasePost(0));

            });

        });

        $grid->quickSearch('title');

        $grid->column('id', __('Id'))->sortable();

        $grid->column('parent_id', __('分类'))->display(function ($parent_id) {
            $cates = Categorie::where('id',$parent_id)->first();
            if($cates)
                return "<span style='color:red'>$cates->title</span>";
            else
                return "无";
        });

        $grid->column('title', __('商品名称'))->display(function ($title) {
            return "<span style='color:blue'>$title</span>";
        });

        $grid->column('amount', __('库存'));

        $grid->column('price', __('价格'));

        $grid->column('brand', __('商品品牌'));

        $grid->column('status', __('状态'))->using(['1' => '通过', '2'=> '待审', '3'=> '拒绝', '4'=> '下架', '5'=> '删除']);

        $grid->column('created_at', __('添加时间'));

        $grid->column('updated_at', __('修改时间'))->hide();

        $grid->model()->orderBy('id', 'desc');

        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            $filter->column(1/2, function ($filter) {
                $filter->like('title', '名称');
            });

            $filter->column(1/2, function ($filter) {
                $filter->equal('parent_id', '分类')
                    ->select(Categorie::where('mid',5)->where('status',1)->pluck( 'typename','id'));

            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('status', '状态')->select(['1' => '通过', '2'=> '待审', '3'=> '拒绝', '4'=> '下架', '5'=> '删除']);

            });
            $filter->column(1/2, function ($filter) {
                $filter->between('created_at','时间')->datetime();

            });

        });
        return $grid;
    }

    public function release(Request $request)
    {
      $status = $request->get('status');
      $ids = explode(',', $request->get('ids'));

      foreach ($ids as $v) {
           $post = Mall::find($v);
           $post->status = $status;
           $post->save();
      }
    }
   
    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {


        $form = new Form(new Mall);

         // $id = isset(request()->route()->parameters()['ask']) ? request()->route()->parameters()['ask'] : null;

        //隐藏右上角查看 删除按钮
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->select('parent_id','商品分类')->options(
            Categorie::where('mid','5')->pluck('typename', 'id')
        );

        $form->text('title', __('商品名称'))->required();

        $form->number('level', __('等级'))->value(1);

        $form->number('amount', __('商品库存(件)'));

        $form->number('price', __('商品单价(￥)'));

        $form->number('num', __('销售量'));

        $form->text('brand', __('商品品牌'));

        $form->select('province','省份')->options(
              Area::where('parent_id',0)->orderBy('id','asc')->pluck('title', 'id')
          )->load('city', '/admin/api/city');

        $form->select('city','城市');
        
        $form->image('litpic', __('商品单图'))->uniqueName()->removable();
         //图集上传
        $form->multipleImage('thumb','商品图集【可传多个】')->uniqueName()->removable();

        $form->ueditor('content', __('商品详情'));

        $form->radio('status', __('状态'))->options(['1' => '通过', '2'=> '待审', '3'=> '拒绝', '4'=> '下架', '5'=> '删除'])->default('1');

        $form->text('n1', __('补充信息1'))->value('示例：货到付款');

        $form->text('n2', __('补充信息2'))->value('示例：包邮 / 满88包邮');

        $form->number('hits', __('点击率'))->value(rand(100,500));
        //隐藏
        $form->hidden('author_id', __('添加人'))->value(Auth::guard('admin')->user()->id);

        return $form;
    }


}
