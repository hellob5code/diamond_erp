<?=uif::contentHeader($heading,$master)?>
    <?=uif::linkButton("employees/edit/{$master->id}",'icon-edit','warning')?>
    <?=uif::linkDeleteButton("employees/delete/{$master->id}")?>
    <hr>
<div class="row-fluid">
    <div class="span5 well well-small">  
        <dl class="dl-horizontal">
            <dt>Име и Презиме:</dt>
            <dd><?=$master->fname. ' '. $master->lname?></dd>
            <dt>ДНР:</dt>
            <dd><?=uif::date($master->dateofbirth)?></dd>
            <dt>ЕМБ:</dt>
            <dd><?=$master->ssn?></dd>
            <dt>Брачна Состојба:</dt>
            <dd><?=$master->mstatus?></dd>
            <dt>Пол:</dt>
            <dd><?=($master->gender == 'm' ? 'Машки' : 'Женски')?></dd>
            <dt>Адреса:</dt>
            <dd><?=uif::isNull($master->address)?></dd>
            <dt>Град:</dt>
            <dd><?=uif::isNull($master->name)?></dd>
            <dt>Поштенски Код:</dt>
            <dd><?=uif::isNull($master->postalcode)?></dd>
            <dt>Службен Мобилен:</dt>
            <dd><?=uif::isNull($master->comp_mobile)?></dd>
            <dt>Мобилен:</dt>
            <dd><?=uif::isNull($master->mobile)?></dd>
            <dt>Телефон:</dt>
            <dd><?=uif::isNull($master->phone)?></dd>
            <dt>Е-Меил:</dt>
            <dd><?=uif::isNull($master->email)?></dd>
            <dt>Корисничка Група:</dt>
            <dd><?=uif::isNull($master->role_name)?></dd>
            <dt>Корисничко Име:</dt>
            <dd><?=uif::isNull($master->username)?></dd>
            <dt>Банка:</dt>
            <dd><?=uif::isNull($master->bank)?></dd>
            <dt>Број на Сметка:</dt>
            <dd><?=uif::isNull($master->account_no)?></dd>
            <dt>Само Фиксна Плата:</dt>
            <dd><?=($master->fixed_wage_only) ? 
                uif::staticIcon('icon-ok') : uif::staticIcon('icon-remove')?></dd>
            <dt>Фиксна Плата:</dt>
            <dd><?=uif::isNull($master->fixed_wage)?></dd>
            <dt>Придонеси:</dt>
            <dd><?=uif::isNull($master->social_cont)?></dd>
            <dt>Тел.Субвенција:</dt>
            <dd><?=uif::isNull($master->comp_mobile_sub)?></dd>
            <dt>Работно Место:</dt>
            <dd><?=uif::isNull($master->position)?></dd>
            <dt>Дистрибутер:</dt>
            <dd><?=($master->is_distributer) ? 
                uif::staticIcon('icon-ok') : uif::staticIcon('icon-remove')?></dd>
            <dt>Менаџер:</dt>
            <dd><?=($master->is_manager) ? 
                uif::staticIcon('icon-ok') : uif::staticIcon('icon-remove')?></dd>            
            <dt>Почеток:</dt>
            <dd><?=uif::isNull($master->start_date)?></dd>
            <dt>Крај:</dt>
            <dd><?=uif::isNull($master->stop_date)?></dd>                  	
            <dt>Белешка:</dt>
            <dd><?=uif::isNull($master->note)?></dd>   
        </dl>
    </div>
    <div class="span7">
        <?php if(isset($payrolls) AND count($payrolls)):?>
        <div class="legend">Последни 6 Плати</div>
        <table class="table table-condensed assigned-tasks">
            <thead>
                <tr>
                    <th>Линк</th>
                    <th>Месец</th>
                    <th>Учинок</th>
                    <th>Бруто</th>
                    <th>Доплата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payrolls as $row):?>
                <tr data-tid=<?=$row->id?>>
                    <td><?=anchor("payroll/view/{$row->id}",'#'.$row->id)?></td>
                    <td><?=uif::date($row->date_from,'%m/%Y')?></td>
                    <td><?=$row->acc_wage?></td>
                    <td><?=$row->gross_wage?></td>
                    <td><?=$row->paid_wage?></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
        <?php endif;?>
         <hr>
        <div class="legend">Доделување работни задачи</div>
        <?=form_open("employees/assignTask")?>
            <div class="well well-small form-horizontal">
                <?=uif::formElement('dropdown','','task_fk',[$tasks],"id='task' class='input-xlarge'")?>
                <?=form_hidden('employee_fk',$master->id)?>
                <?=uif::button('icon-plus-sign','success',"id='assign-task'")?>
            </div>  
        <?=form_close()?>
        <?php if(isset($assigned_tasks) AND count($assigned_tasks)):?>
        <div class="legend">Доделени работни задачи</div>
        <table class="table table-condensed assigned-tasks">
            <thead>
                <tr>
                    <th>Работна Задача</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_tasks as $task):?>
                <tr data-tid=<?=$task->id?>>
                    <td><?=$task->taskname?></td>
                    <td><?=uif::linkButton("employees/unassignTask/{$task->id}",'icon-trash','danger btn-mini')?></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
        <?php endif;?>
    </div>
</div>
<script>
    $(function(){
        cd.dd("select[name=task_fk]",'Работна Задача');

        $("#assign-task").on("click",function(){
            var task = $("#task option:selected");
            if(task.val() == ''){
                alert("Изберете работна задача.");
                $("#task").focus();
                return false;
            }
        });
    });
</script>