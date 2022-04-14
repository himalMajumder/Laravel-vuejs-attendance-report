<template>
  <v-app id="inspire">
    <v-container class="grey lighten-5">
      <v-row no-gutters>
        <v-col cols="12" sm="6" md="8">
          <v-card class="m-3 p-3">
            <v-card-title>
              <h2 class="font-weight-light mb-2">
                {{ heading }}
              </h2>
            </v-card-title>
            <v-card-actions>
              <v-row no-gutters>
                <v-col cols="12" sm="8" md="6">
                  <v-text-field
                    label="Employee Id"
                    v-model="search_employee_id"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" sm="8" md="6">
                  <v-btn color="primary" @click="fetchAttendanceData">
                    <v-icon left> mdi-file-search</v-icon> Search
                  </v-btn>
                  <v-btn color="blue-grey" class="ma-2 white--text" @click="generatePdf">
                    <v-icon left> mdi-file-move </v-icon> PDF Generator
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-actions>
            <v-simple-table>
              <template v-slot:default>
                <thead>
                  <tr>
                    <th class="text-left">Sl</th>
                    <th class="text-left">Month</th>
                    <th class="text-left">Date</th>
                    <th class="text-left">Day</th>
                    <th class="text-left">ID</th>
                    <th class="text-left">Employee Name</th>
                    <th class="text-left">Department</th>
                    <th class="text-left">First In Time</th>
                    <th class="text-left">Last Out Time</th>
                    <th class="text-left">Hours Of Work</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, i) in employeeAttendanceData" :key="item.id">
                    <td>{{ i + 1 }}</td>
                    <td>{{ item.month }}</td>
                    <td>{{ item.date }}</td>
                    <td>{{ item.day }}</td>
                    <td>{{ item.employee_id }}</td>
                    <td>{{ item.employee_name }}</td>
                    <td>{{ item.department }}</td>
                    <td
                      v-bind:class="[
                        lateCheck(item.first_in_time, item.date) ? errorClass : '',
                      ]"
                    >
                      {{ item.first_in_time }}
                    </td>
                    <td
                      v-bind:class="[
                        earlyGoingCheck(item.last_out_time, item.date)
                          ? warningClass
                          : '',
                      ]"
                    >
                      {{ item.last_out_time }}
                    </td>
                    <td>{{ item.hours_of_work }}</td>
                  </tr>
                </tbody>
              </template>
            </v-simple-table>
          </v-card>
        </v-col>

        <v-col cols="6" md="4">
          <v-card class="m-3 p-3" outlined tile>
            <a v-if="excelFile !== ''" :href="excelFile" download>
              <v-icon left> mdi-file-excel-outline </v-icon> Excel Format</a
            >
            <form class="mt-10">
              <v-row>
                <v-col cols="12">
                  <input
                    type="file"
                    name="file"
                    id="file"
                    @change="handleFileUpload"
                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  />
                </v-col>
                <v-col cols="12">
                  <v-btn class="mr-4" @click="submitAttendance" color="success">
                    <v-icon left> mdi-content-save-check </v-icon> Submit
                  </v-btn>
                </v-col>
              </v-row>
            </form>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </v-app>
</template>

<script>
import axios from "axios";
import { jsPDF } from "jspdf";
import "jspdf-autotable";

export default {
  data() {
    return {
      employeeAttendanceData: [],
      file: "",
      excelFile: "",
      search_employee_id: "",
      standardInTime: "09:30:00",
      standardOutTime: "17:00:00",
      errorClass: "text-danger",
      warningClass: "text-warning",
      heading: "Attendance Report",
    };
  },
  mounted() {
    // console.log("Component mounted.");
  },

  created() {
    this.fetchAttendanceData();
    this.fetchAttendanceFile();
  },

  methods: {
    async fetchAttendanceData() {
      await axios({
        method: "post",
        params: {
          id: this.search_employee_id,
        },
        url: "/api/all_employee_attendance",
      })
        .then((res) => {
          this.employeeAttendanceData = res.data.data;
        })
        .catch((err) => console.log(err));
    },
    async fetchAttendanceFile() {
      await axios({
        method: "post",
        url: "/api/file_url",
      })
        .then((res) => {
          this.excelFile = res.data;
        })
        .catch((err) => console.log(err));
    },

    handleFileUpload(event) {
      var input = event.target.files[0];
      this.file = input;
    },

    submitAttendance(e) {
      e.preventDefault();
      let currentObj = this;
      const config = {
        headers: {
          "content-type": "multipart/form-data",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
      };
      // form data
      let formData = new FormData();
      formData.append("file", this.file);

      axios
        .post("/api/import_attendance", formData, config)
        .then((res) => {
          this.employeeAttendanceData = res.data.data;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    lateCheck(time, date) {
      let current_in_dateTime = new Date(`${date} ${time}`);
      let standard_in_dateTime = new Date(`${date} ${this.standardInTime}`);
      return current_in_dateTime.getTime() > standard_in_dateTime.getTime();
    },

    earlyGoingCheck(time, date) {
      let current_out_dateTime = new Date(`${date} ${time}`);
      let standard_out_dateTime = new Date(`${date} ${this.standardOutTime}`);
      return current_out_dateTime.getTime() < standard_out_dateTime.getTime();
    },

    generatePdf() {
      const columns = [
        { title: "Month", dataKey: "month" },
        { title: "Date", dataKey: "date" },
        { title: "Day", dataKey: "day" },
        { title: "ID", dataKey: "employee_id" },
        { title: "Employee Name", dataKey: "employee_name" },
        { title: "Department", dataKey: "department" },
        { title: "First In Time", dataKey: "first_in_time" },
        { title: "Last Out Time", dataKey: "last_out_time" },
        { title: "Hours Of Work", dataKey: "hours_of_work" },
      ];
      const doc = new jsPDF({
        orientation: "portrait",
        unit: "in",
        format: "letter",
      });

      doc.setLineWidth(0.01).line(0.5, 1.1, 8.0, 1.1);
      doc.setFontSize(16).text(this.heading, 0.5, 1.0);
      doc.setLineWidth(0.01).line(0.5, 1.1, 8.0, 1.1);

      doc.autoTable({
        columns,
        body: this.employeeAttendanceData,
        margin: { left: 0.5, top: 1.25 },
        tableLineColor: [231, 76, 60],
        didParseCell: function (data) {},
      });

      doc.save("Attendance-report.pdf");
    },

    examplesCustom() {
      var doc = new jsPDF();
      doc.autoTable({
        columns,
        body: this.usersInfo,
        margin: { top: 37 },
        tableLineColor: [231, 76, 60],
        tableLineWidth: 1,
        styles: {
          lineColor: [44, 62, 80],
          lineWidth: 1,
        },
        headStyles: {
          fillColor: [241, 196, 15],
          fontSize: 15,
        },
        bodyStyles: {
          fillColor: [52, 73, 94],
          textColor: 240,
        },
        alternateRowStyles: {
          fillColor: [74, 96, 117],
        },
        // Note that the "email" key below is the same as the column's dataKey used for
        // the head and body rows. If your data is entered in array form instead you have to
        // use the integer index instead i.e. `columnStyles: {5: {fillColor: [41, 128, 185], ...}}`
        columnStyles: {
          email: {
            fontStyle: "bold",
          },
          city: {
            // The font file mitubachi-normal.js is included on the page and was created from mitubachi.ttf
            // with https://rawgit.com/MrRio/jsPDF/master/fontconverter/fontconverter.html
            // refer to https://github.com/MrRio/jsPDF#use-of-utf-8--ttf
            font: "mitubachi",
          },
          id: {
            halign: "right",
          },
        },
        allSectionHooks: true,
        // Use for customizing texts or styles of specific cells after they have been formatted by this plugin.
        // This hook is called just before the column width and other features are computed.
        didParseCell: function (data) {
          if (data.row.index === 5) {
            data.cell.styles.fillColor = [40, 170, 100];
          }

          if (
            (data.row.section === "head" || data.row.section === "foot") &&
            data.column.dataKey === "expenses"
          ) {
            data.cell.text = ""; // Use an icon in didDrawCell instead
          }

          if (data.column.dataKey === "city") {
            data.cell.styles.font = "mitubachi";
            if (data.row.section === "head") {
              data.cell.text = "シティ";
            }
            if (data.row.index === 0 && data.row.section === "body") {
              data.cell.text = "とうきょう";
            }
          }
        },
        // Use for changing styles with jspdf functions or customize the positioning of cells or cell text
        // just before they are drawn to the page.
        willDrawCell: function (data) {
          if (data.row.section === "body" && data.column.dataKey === "expenses") {
            if (data.cell.raw > 750) {
              doc.setTextColor(231, 76, 60); // Red
            }
          }
        },
        // Use for adding content to the cells after they are drawn. This could be images or links.
        // You can also use this to draw other custom jspdf content to cells with doc.text or doc.rect
        // for example.
        didDrawCell: function (data) {
          if (
            (data.row.section === "head" || data.row.section === "foot") &&
            data.column.dataKey === "expenses" &&
            coinBase64Img
          ) {
            doc.addImage(coinBase64Img, "PNG", data.cell.x + 5, data.cell.y + 2, 5, 5);
          }
        },
        // Use this to add content to each page that has the autoTable on it. This can be page headers,
        // page footers and page numbers for example.
        didDrawPage: function (data) {
          doc.setFontSize(18);
          doc.text("Custom styling with hooks", data.settings.margin.left, 22);
          doc.setFontSize(12);
          doc.text(
            "Conditional styling of cells, rows and columns, cell and table borders, custom font, image in cell",
            data.settings.margin.left,
            30
          );
        },
      });
      return doc;
    },
  },
};
</script>
