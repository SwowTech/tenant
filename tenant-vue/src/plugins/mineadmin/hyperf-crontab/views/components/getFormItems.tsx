/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaFormItem } from '@mineadmin/form'
import CrontabGenerator from './crontabGenerator.vue'

export default function getFormItems(formType: 'add' | 'edit' = 'add', t: any, model: any): MaFormItem[] {
  if (formType === 'add') {
    model.status = true
    model.is_singleton = true
    model.is_on_one_server = true
    model.memo = ''
  }

  return [
    {
      label: () => t('mineCrontab.cols.name'),
      prop: 'name',
      render: 'input',
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('mineCrontab.cols.name') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.requiredInput', { msg: t('mineCrontab.cols.name') }) }],
      },
    },
    {
      label: () => t('mineCrontab.cols.type'),
      prop: 'type',
      render: () => <ma-dict-select />,
      renderProps: {
        placeholder: t('form.pleaseSelect', { msg: t('mineCrontab.cols.type') }),
        dictName: 'crontab-type',
      },
      itemProps: {
        rules: [{ required: true, message: t('form.requiredInput', { msg: t('mineCrontab.cols.type') }) }],
      },
    },
    {
      label: () => t('mineCrontab.cols.rule'),
      prop: 'rule',
      render: () => CrontabGenerator,
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('mineCrontab.cols.rule') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.requiredInput', { msg: t('mineCrontab.cols.rule') }) }],
      },
    },
    {
      label: () => t('mineCrontab.cols.value'),
      prop: 'value',
      render: 'input',
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('mineCrontab.cols.value') }),
      },
      itemProps: {
        rules: [{ required: true, message: t('form.requiredInput', { msg: t('mineCrontab.cols.value') }) }],
      },
    },
    {
      label: () => t('mineCrontab.cols.singleton'),
      prop: 'is_singleton',
      render: () => <ma-dict-radio />,
      cols: { md: 12, xs: 24 },
      renderProps: {
        dictName: 'crontab-yes-no',
      },
    },
    {
      label: () => t('mineCrontab.cols.onOneServer'),
      prop: 'is_on_one_server',
      render: () => <ma-dict-radio />,
      cols: { md: 12, xs: 24 },
      renderProps: {
        dictName: 'crontab-yes-no',
      },
    },
    {
      label: () => t('mineCrontab.cols.memo'),
      prop: 'memo',
      render: 'input',
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('mineCrontab.cols.memo') }),
        type: 'textarea',
      },
    },
    {
      label: () => t('crud.status'),
      prop: 'status',
      render: () => <ma-dict-radio />,
      renderProps: {
        dictName: 'crontab-yes-no',
      },
    },
  ]
}
