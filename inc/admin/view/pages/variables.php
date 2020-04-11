<h2>Styling criteria and importance</h2>
<div class="sbws-content">
    <table class="sbws-variables">
        <thead class="sbws-variables-header">
            <tr>
                <th class="sbws-col">Criteria</th>
                <th class="sbws-col">Active</th>
                <th class="sbws-col">Mandatory</th>
                <th class="sbws-col">Score</th>
            </tr>
        </thead>
        <tbody class="sbws-variables-content">
        <?php foreach ($list as $item): ?>
            <tr>
                <td valign="middle">
                    <h4><?php echo $item->option_name; ?></h4>
                    <input type="hidden" class="field_option_id" value="<?php echo $item->option_id; ?>" />
                </td>
                <td><input type="checkbox" class="variables_field_active" name="item_active_<?php echo $item->option_id; ?>" <?php echo $item->option_active == true ? 'checked' : ''; ?>/></td>
                <td><input type="checkbox" class="variables_field_mandatory" name="item_mandatory_<?php echo $item->option_id; ?>" <?php echo $item->option_mandatory == true ? 'checked' : ''; ?> <?php echo $item->option_active == false ? 'disabled' : ''; ?>/></td>
                <td><input type="number" class="variables_field_score" name="item_score_<?php echo $item->option_id; ?>" value="<?php echo $item->option_score; ?>" <?php echo $item->option_mandatory == true || $item->option_active == false ? 'disabled' : ''; ?>/></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="buttons">
        <button type="button" class="button button-primary btn-save-settings" data-tab="variables">Save options</button>
    </div>
</div>
