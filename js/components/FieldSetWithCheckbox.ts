import { WP2StaticGlobals } from "../WP2StaticGlobals"

export class FieldSetWithCheckbox {

  public wp2staticGlobals: WP2StaticGlobals

  public getComponent () {
    return {
      data: () => {
        return {
          count: 0,
        }
      },
      methods: {
        checkboxChanged: (id: string) => {
          const element: HTMLInputElement =
            document.getElementById(id)! as HTMLInputElement

          const checked: boolean = element.checked

          this.wp2staticGlobals.vueData.options[id] = checked
        },
      },
      props: [
        "checked",
        "description",
        "hint",
        "id",
      ],
      template: `
    <div>
      <p>{{ description }}</p>

      <fieldset>
        <label :for='id'>
          <input
            :name='id'
            :id='id'
            value='1'
            type='checkbox'
            :checked='checked'
            v-on:change="checkboxChanged(id)"
            />
          <span>{{ hint }}</span>
        </label>
      </fieldset>
    </div>`,
    }
  }

  constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals
  }

}
