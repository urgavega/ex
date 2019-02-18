const gl_url_api = 'http://ex.test/api/';


$(document).ready(function() {

  const dateFormat = "yy-mm-dd",
    from = $( "#from" )
      .datepicker({
        //defaultDate: "-2m",
        changeMonth: true,
        numberOfMonths: 1,
        dateFormat: dateFormat
      })
      .on( "change", function() {
        to.datepicker( "option", "minDate", app.validateDate( this ) );
      }),
    to = $( "#to" ).datepicker({
      //defaultDate: "-2m",
      changeMonth: true,
      numberOfMonths: 1,
      dateFormat: dateFormat
    })
      .on( "change", function() {
        from.datepicker( "option", "maxDate", app.validateDate( this ) );
      });

} );



const spinner = new Spinner({
  lines: 10,
  length: 30,
  width: 8,
  radius: 35,
  scale: 1.00,
  corners: 1.0,
  opacity: 0.25,
  rotate: 0,
  direction: 1,
  speed: 1.0,
  trail: 60,
  shadow: 'on',
  zIndex: 2e9,
  top: '50%', // Top position relative to parent
  left: '50%', // Left position relative to parent
  position: 'fixed' // Element positioning
});



const app = {
  csv: {
    headers: [],
    rows: [],
  },

  validateDate : function ( element ) {
    let date;
    const title = $(element).attr('title');
    const id = $(element).attr('id');
    const val = $(element).val();

    const $dateError = $('#dateError');
    $dateError.find('small').hide();
    $(element).removeClass('is-invalid').addClass('is-valid');


    if(!val) {
      $(element).removeClass('is-valid').addClass('is-invalid');
      $dateError.find('[name=empty]').show();
      return;
    }


    const momentDate = moment(val);
    if(!momentDate.isValid()) {
      $(element).removeClass('is-valid').addClass('is-invalid');
      $dateError.find('[name=wrong]').show();
      return;
    }


    $('#' + id).datepicker("setDate", momentDate.format('YYYY-MM-DD'));

    return momentDate.format('YYYY-MM-DD');
  },


  getData : function(page, csv = false){
    const self = this;
    page = page || 1;

    let isErrors  = +!this.validateDate($('#to'))
      + +!this.validateDate($('#from'));
    if(isErrors) return;

    const userId = $('#userId').val();

    const data_src = {
      userId: userId,
      from: $('#from').val() || null,
      to: $('#to').val() || null,
      page: page
    };
    const opts = { url: 'report', type: 'get' };
    this.csAjax(data_src, opts, function(data) {
      if(data.errors) {
        swal('Ooops!', JSON.stringify(data.errors), 'error');
        return;
      }
      if(!_.size(data.rows)){
        swal('Ooops!', 'No data', 'error');
        return;
      }

      self.buildTableAgg(data);
      self.buildTable(data);
      self.paginationStart(+data['pages'], +data['page']);
    });
  },


  csAjax : function (data_src, opts, callback) {
    $.ajax({
      url: gl_url_api + opts.url,
      type: opts.type,
      data: data_src,
      jsonp: 'callback',
      beforeSend: function(xhr) {
        if (!opts.no_spinner) $('#spinnerContainer').after(spinner.spin().el);
      },
      success: function(data) {
        callback.call(this, data);
      },
      error: function(data, textStatus, errorThrown) {

        console.log(textStatus);
        console.log(errorThrown);
      },
      complete: function() { if (!opts.no_spinner) spinner.stop(); }
    });
  },


  buildTable: function (data) {
    const self = this;

    const $tbl = $('#tbl');
    const tmpl = $('#tbl tbody tr[name=template_tr]').prop('outerHTML');
    $tbl.find('tbody').empty().append(tmpl);

    if(!data.rows || !_.size(data.rows)) {
      $tbl.hide();
      return;
    }
    $tbl.show();

    $.each(data.rows, function(index, v) {

      let item = $.nano(tmpl,{
        index: index,
        id: v.id,
        action: v.action + ((v.action === 'transferCredit' && v.amount > 0) ? ' <br>income' : ' <br>withdraw'),
        date: v.date,
        wallet_id: v.wallet_id,
        wallet_name: v.wallet_name,
        wallet_currency: v.wallet_currency,
        secondary_wallet_id: v.secondary_wallet_id,
        secondary_wallet_name: v.secondary_wallet_name,
        secondary_wallet_currency: v.secondary_wallet_currency,
        s_wallet: v.secondary_wallet_id ?
          'ID ' + v.secondary_wallet_id
          + ' <br>' + v.secondary_wallet_name
          + ' <br>(' + v.secondary_wallet_currency + ')'
          + ' <br>' + v.secondary_user_name
          : '-',
        amount: v.amount.toFixed(2),
        amount_usd: v.amount_usd.toFixed(2),
      });
      item = $(item).attr('name', 'row').css('display', '');
      $tbl.find('tbody').append(item);
    });
  },

  buildTableAgg: function (data) {
    const self = this;

    const $tbl = $('#tblAgg');
    const tmpl = $('#tblAgg tbody tr[name=template_tr]').prop('outerHTML');
    $tbl.find('tbody').empty().append(tmpl);

    const $amountSumUsd = $('#amountSumUsd');

    if(!data.agg || !_.size(data.agg)) {
      $tbl.hide();
      $amountSumUsd.hide();
      return;
    }
    $tbl.show();
    $amountSumUsd.show();

    $amountSumUsd.find('[name=amount]').text(data.amountSumUsd);

    $.each(data.agg, function(index, v) {
      let item = $.nano(tmpl,{
        index: index,
        wallet_id: v.wallet_id,
        wallet_name: v.wallet_name,
        wallet_currency: v.wallet_currency,
        balance: v.balance,
        sum: v.sum,
        sum_usd: v.sum_usd,
        cnt: v.cnt,
      });
      item = $(item).attr('name', 'row').css('display', '');
      $tbl.find('tbody').append(item);
    });
  },

  paginationStart: function (totalPages, startPage) {
    const self = this;
    const $pagination = $('#pagination');
    $pagination.twbsPagination('destroy');

    if (totalPages > 1) {
      $pagination.twbsPagination({
        totalPages: totalPages,
        startPage: startPage,
        visiblePages: 10,
        href: false,
        initiateStartPageClick: false,
        onPageClick: function (event, page) {
          self.getData(page);
        }
      });
    }
    return true;
  },

  getDataCSV: function(page){
    const self = this;
    page = page || 1;

    if (page === 1) {
      swal('Waiting', 'Loading...','warning');
      self.csv = {
        headers: [],
        rows: [],
      };
    }

    let isErrors  = +!this.validateDate($('#to'))
      + +!this.validateDate($('#from'));
    if(isErrors) return;


    const userId = $('#userId').val();

    const data_src = {
      userId: userId,
      from: $('#from').val() || null,
      to: $('#to').val() || null,
      page: page
    };
    const opts = { url: 'report', type: 'get', no_spinner: true};
    this.csAjax(data_src, opts, function(data) {
      if(data.errors) {
        swal('Ooops!', JSON.stringify(data.errors), 'error');
        return;
      }

      let arr = _.map(data.rows, function (item) {
        if (!_.size(self.csv.headers)) {
          self.csv.headers = (_.keys(item)).join(',') + '\n';
        }
        item.balance = item.balance.toFixed(2);
        item.amount = item.amount.toFixed(2);
        item.amount_usd = item.amount_usd.toFixed(2);
        return (_.values(item)).join(',') + '\n';
      });
      self.csv.rows = _.concat(self.csv.rows, arr);

      if (+data.page < +data['pages']) {
        swal('Waiting', 'Loading... ' + (+data.page + 1) + '/' + data['pages'],'warning');
        self.getDataCSV(++page);
      } else {
        if(!_.size(self.csv.rows)){
          swal('Ooops!', 'No data', 'error');
          return;
        }

        swal.close();
        const csvData = new Blob(_.concat(self.csv.headers, self.csv.rows), {type: 'text/csv;charset=utf-8;'});
        const csvURL = window.URL.createObjectURL(csvData);
        const tempLink = document.createElement('a');
        tempLink.href = csvURL;
        tempLink.setAttribute('download', 'export.csv');
        tempLink.click();

        return false;
      }
    });
  },

  sendToCSV: function () {

  }
};