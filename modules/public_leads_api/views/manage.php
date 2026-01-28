<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Public API Settings</h4>
          </div>
          <div class="panel-body">
            <?php echo form_open(admin_url('public_leads_api')); ?>
              <input type="hidden" name="action" value="settings">
              <div class="form-group">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" name="enabled" id="pla-enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                  <label for="pla-enabled">Enable public endpoint</label>
                </div>
              </div>
              <div class="form-group">
                <label for="rate_limit">Rate limit (requests)</label>
                <input type="number" min="0" class="form-control" name="rate_limit" id="rate_limit" value="<?php echo html_escape($rate_limit); ?>">
                <p class="text-muted mtop5">0 = unlimited</p>
              </div>
              <div class="form-group">
                <label for="rate_window">Per window (minutes)</label>
                <input type="number" min="1" class="form-control" name="rate_window" id="rate_window" value="<?php echo html_escape($rate_window); ?>">
                <p class="text-muted mtop5">Example: 120 requests per 10 minutes.</p>
              </div>
              <div class="form-group">
                <label for="blocked_ips">Blocked IPs</label>
                <textarea class="form-control" name="blocked_ips" id="blocked_ips" rows="3" placeholder="one or more IPs, comma or newline separated"><?php echo html_escape($blocked_ips); ?></textarea>
                <p class="text-muted mtop5">Requests from these IPs are rejected before rate limiting.</p>
              </div>
              <button type="submit" class="btn btn-primary">Save Settings</button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Generate API Key</h4>
          </div>
          <div class="panel-body">
            <?php echo form_open(admin_url('public_leads_api')); ?>
              <input type="hidden" name="action" value="generate">
              <div class="form-group">
                <label for="label">Label</label>
                <input type="text" class="form-control" name="label" id="label" placeholder="e.g. WordPress site" required>
              </div>
              <button type="submit" class="btn btn-success">Generate Key</button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">API Keys</h4>
          </div>
          <div class="panel-body table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Label</th>
                  <th>Key</th>
                  <th>Status</th>
                  <th>Usage</th>
                  <th>Last Used</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($keys as $key): ?>
                  <tr>
                    <td><?php echo html_escape($key['label']); ?></td>
                    <td><code><?php echo html_escape($key['api_key']); ?></code></td>
                    <td><?php echo $key['active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Revoked</span>'; ?></td>
                    <td><?php echo (int) $key['usage_count']; ?></td>
                    <td><?php echo html_escape($key['last_used_at'] ?: '—'); ?></td>
                    <td><?php echo html_escape($key['created_at']); ?></td>
                    <td>
                      <?php if ($key['active']): ?>
                        <a href="<?php echo admin_url('public_leads_api/revoke/' . $key['id']); ?>" class="btn btn-xs btn-warning">Revoke</a>
                      <?php endif; ?>
                      <a href="<?php echo admin_url('public_leads_api/delete/' . $key['id']); ?>" class="btn btn-xs btn-danger _delete">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Recent API Calls</h4>
          </div>
          <div class="panel-body table-responsive">
            <table class="table table-striped dt-table" id="pla-logs-table" data-order-col="0" data-order-type="desc">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Status</th>
                  <th>Message</th>
                  <th>Lead ID</th>
                  <th>IP</th>
                  <th>At</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">API Usage Guide</h4>
          </div>
          <div class="panel-body">
            <p><strong>Endpoint 1</strong></p>
            <pre>POST <?php echo site_url('api/public/leads/store'); ?></pre>

            <p><strong>Endpoint 2</strong></p>
            <pre>POST <?php echo site_url('public_leads_api/public_api/store'); ?></pre>

            <p><strong>Headers</strong></p>
            <pre>X-API-KEY: &lt;your_api_key&gt;
Content-Type: application/json</pre>

            <p><strong>Payload (CRM lead fields)</strong></p>
            <p class="text-muted mtop5">Required: <code>name</code> (request is rejected if missing/blank). <code>status</code> and <code>source</code> fall back to your default CRM values if omitted. Any extra key becomes a Lead custom field; empty values are stored as <code>"-"</code> (or <code>0</code> for numeric fields).</p>
            <pre>{
  "name": "Jane Doe",                  // required lead name
  "title": "Marketing Manager",
  "company": "Acme Inc",
  "email": "jane@example.com",
  "website": "https://acme.com",
  "phonenumber": "+1 222 333 4444",
  "address": "123 Main St",
  "city": "Austin",
  "state": "TX",
  "zip": "73301",
  "country": 226,
  "description": "Project notes or context",
  "assigned": 5,
  "status": "New Lead",
  "source": "Website",
  "lead_value": 6500,
  "tags": ["web", "inbound"],
  "default_language": "en",
  "custom_field_example": "Any extra key becomes a lead custom field"
}</pre>

            <p><strong>Success Response</strong></p>
            <pre>{
  "status": true,
  "message": "Lead created successfully",
  "lead_id": 123
}</pre>

            <p><strong>Error Response</strong></p>
            <pre>{
  "status": false,
  "message": "Invalid API Key"
}</pre>

            <p><strong>cURL</strong></p>
            <pre>curl -X POST "YOUR_ENDPOINT" \
  -H "X-API-KEY: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane Doe","title":"Marketing Manager","company":"Acme Inc","email":"jane@example.com","website":"https://acme.com","phonenumber":"+1 222 333 4444","address":"123 Main St","city":"Austin","state":"TX","zip":"73301","country":226,"description":"Project notes or context","assigned":5,"status":"New Lead","source":"Website","lead_value":6500,"tags":["web","inbound"],"default_language":"en","custom_field_example":"Any extra key becomes a lead custom field"}'</pre>

            <p class="text-muted mtop10">Accepts <code>application/json</code> or <code>multipart/form-data</code>. Each request creates one lead and logs the attempt.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Reference Data</h4>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <h5>Sources</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($sources as $source): ?>
                    <li><code><?php echo (int) $source['id']; ?></code> <?php echo html_escape($source['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Statuses</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($statuses as $status): ?>
                    <li><code><?php echo (int) $status['id']; ?></code> <?php echo html_escape($status['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Staff (Assignable)</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($staff as $member): ?>
                    <li><code><?php echo (int) $member['id']; ?></code> <?php echo html_escape($member['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Tags</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($tags as $tag): ?>
                    <li><code><?php echo (int) $tag['id']; ?></code> <?php echo html_escape($tag['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <p class="text-muted mtop10">Use staff ID for <code>assigned</code>; other fields accept Names shown.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function() {
    var lists = document.querySelectorAll('.ref-list');
    lists.forEach(function(list) {
      var limit = parseInt(list.getAttribute('data-limit') || '10', 10);
      var items = Array.prototype.slice.call(list.querySelectorAll('li'));
      if (items.length <= limit) {
        return;
      }
      items.slice(limit).forEach(function(li) { li.style.display = 'none'; });

      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'btn btn-link btn-xs pleft0';
      toggle.textContent = 'Read more';
      toggle.dataset.state = 'collapsed';

      toggle.addEventListener('click', function() {
        var collapsed = toggle.dataset.state === 'collapsed';
        items.slice(limit).forEach(function(li) {
          li.style.display = collapsed ? '' : 'none';
        });
        toggle.textContent = collapsed ? 'Read less' : 'Read more';
        toggle.dataset.state = collapsed ? 'expanded' : 'collapsed';
      });

      list.parentNode.appendChild(toggle);
    });
  })();
</script>
<script>
  (function() {
    "use strict";
    function initLogsTable($) {
      if (!$.fn.DataTable) { return; }
      $('#pla-logs-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        ajax: {
          url: admin_url + 'public_leads_api/logs',
          type: 'POST'
        },
        columns: [
          { data: 0 },
          { data: 1 },
          { data: 2 },
          { data: 3 },
          { data: 4 },
          { data: 5 }
        ]
      });
    }

    if (window.jQuery) {
      initLogsTable(window.jQuery);
    } else {
      // Fallback if jQuery loads later
      var interval = setInterval(function() {
        if (window.jQuery) {
          clearInterval(interval);
          initLogsTable(window.jQuery);
        }
      }, 50);
    }
  })();
</script>
<?php init_tail(); ?>
