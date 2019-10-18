import { WP2StaticGlobals } from "../WP2StaticGlobals"

export class DetectionCheckbox {

  public wp2staticGlobals: WP2StaticGlobals

  public getComponent () {
    return {
      // TODO: kill unused data
      data: () => {
        return {
          count: 0,
        }
      },
      methods: {
        detectionCheckboxChanged: (id: string) => {
          const element: HTMLInputElement =
            document.getElementById(id)! as HTMLInputElement
          const checked: boolean = element.checked

          const checkbox =
            this.wp2staticGlobals.vueData.detectionCheckboxes.filter(
              (obj: any) => obj.id ===  id,
          )

          checkbox[0].checked = checked

          this.wp2staticGlobals.vueData[id] = checked
        },
        getInitialState: (id: string) => {
          return this.wp2staticGlobals.vueData[id]
        },
      },
      props: [
        "description",
        "id",
        "title",
      ],
      template: `
      <tr>
          <td>
              <label :for='id'>
              <b>{{ title }}</b>
              </label>
          </td>
          <td>
              <fieldset>
                  <label :for='id'>
                      <input
                        :id='id'
                        :name='id'
                        type='checkbox'
                        v-on:change="detectionCheckboxChanged(id)"
                        :checked='getInitialState(id)'
                        value='1'
                      />
                      <span>{{ description }}</span>
                  </label>
              </fieldset>
          </td>
      </tr>`,
    }
  }

  constructor( wp2staticGlobals: WP2StaticGlobals ) {
      this.wp2staticGlobals = wp2staticGlobals
  }

}
